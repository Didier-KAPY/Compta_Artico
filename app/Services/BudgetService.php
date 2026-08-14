<?php

namespace App\Services;

use App\Models\EngagementBudgetaire;
use App\Models\Entreprise;
use App\Models\EtatBesoin;
use App\Models\LigneBudgetaire;
use App\Models\MouvementBudgetaire;
use App\Models\RealisationBudgetaire;
use App\Models\SortieCaisse;
use App\Models\TauxDeChange;
use App\Models\BudgetExercice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BudgetService
{
    public function __construct(private AuditLogService $audit, private BudgetExecutionService $execution) {}

    public function engagerEtat(EtatBesoin $etat): ?EngagementBudgetaire
    {
        if (! config('features.budget')) return null;
        if (! $etat->ligne_budgetaire_id) return null;

        return DB::transaction(function () use ($etat) {
            $etat = EtatBesoin::lockForUpdate()->findOrFail($etat->id);
            if ($existant = EngagementBudgetaire::where('etat_besoin_id', $etat->id)->first()) return $existant;

            $ligne = LigneBudgetaire::with('budget')->lockForUpdate()->findOrFail($etat->ligne_budgetaire_id);
            if ($ligne->statut !== 'Active' || $ligne->budget?->statut !== 'Validé') {
                throw ValidationException::withMessages(['ligne_budgetaire_id' => "La ligne budgétaire doit appartenir à un budget validé et ouvert."]);
            }
            if (($ligne->date_debut && $etat->date->lt($ligne->date_debut)) || ($ligne->date_fin && $etat->date->gt($ligne->date_fin))) {
                throw ValidationException::withMessages(['ligne_budgetaire_id' => 'La date de l’État de besoin est en dehors de la période de la ligne budgétaire.']);
            }
            $entreprise = Entreprise::findOrFail($ligne->entreprise_id);
            [$taux, $dateTaux] = $this->tauxApplicable($etat->monnaie, $ligne->budget->monnaie, $etat->date, $entreprise->id);
            $montantOriginal = (float) $etat->montant_estime;
            $montantBudgetaire = round($montantOriginal * $taux, 2);
            if ($montantBudgetaire <= 0) throw ValidationException::withMessages(['montant_estime' => 'Le montant à engager doit être supérieur à zéro.']);

            $ligne->refresh();
            $realiseComptable = $this->execution->realisePourLigne($ligne);
            $disponible = $ligne->budget_revise - (float) $ligne->engagements_actifs - $realiseComptable;
            if ($montantBudgetaire > $disponible) {
                throw ValidationException::withMessages(['budget' => 'Budget insuffisant. Disponible : '.number_format($disponible, 2, ',', ' ').' '.$ligne->budget->monnaie.'. Montant demandé : '.number_format($montantBudgetaire, 2, ',', ' ').' '.$ligne->budget->monnaie.'.']);
            }
            $avant = $this->situation($ligne);
            $engagement = EngagementBudgetaire::create([
                'budget_exercice_id'=>$ligne->budget_exercice_id,'ligne_budgetaire_id'=>$ligne->id,'etat_besoin_id'=>$etat->id,
                'entreprise_id'=>$ligne->entreprise_id,'montant_original'=>$montantOriginal,'monnaie_originale'=>$etat->monnaie,
                'taux_change'=>$taux,'date_taux'=>$dateTaux,'montant_budgetaire'=>$montantBudgetaire,'montant_restant'=>$montantBudgetaire,
                'montant_realise'=>0,'statut'=>'Actif','date_engagement'=>now(),'utilisateur_id'=>Auth::id(),'motif'=>$etat->motif,
            ]);
            $ligne->increment('engagements_actifs', $montantBudgetaire);
            $ligne->refresh();
            $this->mouvement($ligne, 'engagement', $montantBudgetaire, $etat, $etat->numero, $avant, $this->situation($ligne), $engagement);
            $this->audit->record('engagement_budgetaire', $engagement, $etat->numero, $etat->motif, null, $engagement->attributesToArray(), [], request());
            return $engagement;
        }, 5);
    }

    public function realiserSortie(SortieCaisse $sortie): ?RealisationBudgetaire
    {
        if (! config('features.budget')) return null;
        if (! $sortie->etat_besoin_id) return null;
        return DB::transaction(function () use ($sortie) {
            $sortie = SortieCaisse::lockForUpdate()->findOrFail($sortie->id);
            if ($existante = RealisationBudgetaire::where('sortie_caisse_id', $sortie->id)->first()) return $existante;
            $engagement = EngagementBudgetaire::where('etat_besoin_id', $sortie->etat_besoin_id)->lockForUpdate()->first();
            if (! $engagement) return null;
            if (! in_array($engagement->statut, ['Actif', 'Partiellement réalisé'], true)) {
                throw ValidationException::withMessages(['budget' => "L'engagement budgétaire n'est plus réalisable."]);
            }
            $ligne = LigneBudgetaire::lockForUpdate()->findOrFail($engagement->ligne_budgetaire_id);
            $montant = $sortie->monnaie === $engagement->monnaie_originale
                ? round((float)$sortie->montant * (float)$engagement->taux_change, 2)
                : (float)$engagement->montant_restant;
            $montant = min($montant, (float)$engagement->montant_restant);
            if ($montant <= 0) throw ValidationException::withMessages(['budget' => 'Aucun montant engagé ne reste à réaliser.']);
            $avant = $this->situation($ligne);
            $realisation = RealisationBudgetaire::create([
                'engagement_budgetaire_id'=>$engagement->id,'sortie_caisse_id'=>$sortie->id,'montant_original'=>$sortie->montant,
                'monnaie_originale'=>$sortie->monnaie,'taux_change'=>$engagement->taux_change,'montant_budgetaire'=>$montant,
                'date_realisation'=>now(),'utilisateur_id'=>Auth::id(),'statut'=>'Validée',
            ]);
            $engagement->decrement('montant_restant', $montant);
            $engagement->increment('montant_realise', $montant);
            $engagement->refresh()->update(['statut'=>(float)$engagement->montant_restant > 0 ? 'Partiellement réalisé' : 'Réalisé']);
            $ligne->decrement('engagements_actifs', $montant);
            $ligne->increment('realisations', $montant);
            $ligne->refresh();
            $this->mouvement($ligne, 'réalisation', $montant, $sortie, $sortie->numero, $avant, $this->situation($ligne), $engagement, $realisation);
            $this->audit->record('realisation_budgetaire', $realisation, $sortie->numero, $sortie->motif, null, $realisation->attributesToArray(), [], request());
            return $realisation;
        }, 5);
    }

    public function libererEtat(EtatBesoin $etat, string $motif): void
    {
        if (! config('features.budget')) return;
        DB::transaction(function () use ($etat, $motif) {
            $engagement = EngagementBudgetaire::where('etat_besoin_id', $etat->id)->lockForUpdate()->first();
            if (! $engagement || ! in_array($engagement->statut, ['Actif','Partiellement réalisé'], true)) return;
            $ligne = LigneBudgetaire::lockForUpdate()->findOrFail($engagement->ligne_budgetaire_id);
            $montant = (float)$engagement->montant_restant;
            if ($montant <= 0) return;
            $avant = $this->situation($ligne);
            $ligne->decrement('engagements_actifs', $montant);
            $engagement->update(['montant_restant'=>0,'statut'=>(float)$engagement->montant_realise > 0 ? 'Partiellement réalisé' : 'Libéré','motif'=>$motif]);
            $ligne->refresh();
            $this->mouvement($ligne, 'désengagement', $montant, $etat, $etat->numero, $avant, $this->situation($ligne), $engagement, null, $motif);
            $this->audit->record('desengagement_budgetaire', $engagement, $etat->numero, $motif, null, $engagement->fresh()->attributesToArray(), [], request());
        }, 5);
    }

    public function reviser(LigneBudgetaire $ligne, float $montant, string $sens, string $motif): LigneBudgetaire
    {
        return DB::transaction(function () use ($ligne,$montant,$sens,$motif) {
            $ligne=LigneBudgetaire::with('budget')->lockForUpdate()->findOrFail($ligne->id);
            if ($ligne->budget->statut !== 'Validé') throw ValidationException::withMessages(['budget'=>'Seul un budget validé et ouvert peut être révisé.']);
            if ($montant<=0 || !in_array($sens,['positive','negative'],true)) throw ValidationException::withMessages(['montant'=>'Révision invalide.']);
            if ($sens==='negative' && $montant>$ligne->disponible) throw ValidationException::withMessages(['montant'=>'La révision négative rendrait le disponible inférieur à zéro.']);
            $avant=$this->situation($ligne);
            $ligne->increment($sens==='positive'?'revisions_positives':'revisions_negatives',$montant);
            $ligne->refresh();
            $this->mouvement($ligne,'révision_'.$sens,$montant,$ligne,$ligne->code,$avant,$this->situation($ligne),null,null,$motif);
            $this->audit->record('revision_budgetaire',$ligne,$ligne->code,$motif,$avant,$this->situation($ligne),[],request());
            return $ligne;
        },5);
    }

    public function transferer(LigneBudgetaire $source, LigneBudgetaire $destination, float $montant, string $motif): void
    {
        DB::transaction(function () use ($source,$destination,$montant,$motif) {
            if ($source->id===$destination->id || $montant<=0) throw ValidationException::withMessages(['montant'=>'Transfert invalide.']);
            $ids=collect([$source->id,$destination->id])->sort()->values();
            $locked=LigneBudgetaire::with('budget')->whereIn('id',$ids)->lockForUpdate()->get()->keyBy('id');
            $source=$locked[$source->id]; $destination=$locked[$destination->id];
            if ($source->budget_exercice_id!==$destination->budget_exercice_id || $source->budget->statut!=='Validé') throw ValidationException::withMessages(['destination'=>'Les lignes doivent appartenir au même budget validé.']);
            if ($montant>$source->disponible) throw ValidationException::withMessages(['montant'=>'Disponible insuffisant sur la ligne source.']);
            $avantSource=$this->situation($source); $avantDestination=$this->situation($destination); $uuid=(string)Str::uuid();
            $source->increment('revisions_negatives',$montant); $destination->increment('revisions_positives',$montant);
            $source->refresh(); $destination->refresh();
            foreach ([[$source,'transfert_sortant',$avantSource],[$destination,'transfert_entrant',$avantDestination]] as [$ligne,$type,$avant]) {
                MouvementBudgetaire::create(['budget_exercice_id'=>$ligne->budget_exercice_id,'ligne_budgetaire_id'=>$ligne->id,'operation_uuid'=>$uuid,'type'=>$type,'montant'=>$montant,'monnaie'=>$ligne->budget->monnaie,'source_type'=>LigneBudgetaire::class,'source_id'=>$source->id,'reference_document'=>$source->code.' → '.$destination->code,'ancienne_situation'=>$avant,'nouvelle_situation'=>$this->situation($ligne),'utilisateur_id'=>Auth::id(),'date_mouvement'=>now(),'motif'=>$motif]);
            }
            $this->audit->record('transfert_budgetaire',$source,$source->code.' → '.$destination->code,$motif,$avantSource,$this->situation($source),[],request());
        },5);
    }

    public function changerStatut(BudgetExercice $budget, string $statut, ?string $motif=null): BudgetExercice
    {
        return DB::transaction(function () use ($budget,$statut,$motif) {
            $budget=BudgetExercice::lockForUpdate()->findOrFail($budget->id); $avant=$budget->attributesToArray();
            $updates=['statut'=>$statut];
            if ($statut==='Validé') $updates+=['valide_par'=>Auth::id(),'date_validation'=>now()];
            if ($statut==='Clôturé') $updates+=['cloture_par'=>Auth::id(),'date_cloture'=>now()];
            if ($statut==='Validé' && $budget->statut==='Clôturé') $updates+=['cloture_par'=>null,'date_cloture'=>null];
            $budget->update($updates);
            $this->audit->record('statut_budgetaire',$budget,(string)$budget->exercice,$motif,$avant,$budget->fresh()->attributesToArray(),[],request());
            return $budget->fresh();
        });
    }

    private function tauxApplicable(string $source, string $cible, $date, int $entrepriseId): array
    {
        if ($source === $cible) return [1.0, $date->toDateString()];
        $taux = TauxDeChange::query()->where('devise_source',$source)->where('devise_cible',$cible)
            ->where(fn($q)=>$q->where('entreprise_id',$entrepriseId)->orWhereNull('entreprise_id'))
            ->where(fn($q)=>$q->whereDate('date_taux','<=',$date)->orWhereNull('date_taux'))
            ->orderByRaw('date_taux IS NULL')->latest('date_taux')->latest('id')->first();
        if (! $taux || (float)$taux->taux_de_change <= 0) throw ValidationException::withMessages(['taux_change'=>'Aucun taux de change valide n’est disponible à la date de l’État de besoin.']);
        return [(float)$taux->taux_de_change, ($taux->date_taux ?? $taux->created_at)->toDateString()];
    }

    private function situation(LigneBudgetaire $ligne): array { return ['budget_revise'=>$ligne->budget_revise,'engagements_actifs'=>(float)$ligne->engagements_actifs,'realisations'=>(float)$ligne->realisations,'disponible'=>$ligne->disponible]; }
    private function mouvement(LigneBudgetaire $ligne,string $type,float $montant,$source,?string $reference,array $avant,array $apres,?EngagementBudgetaire $engagement=null,?RealisationBudgetaire $realisation=null,?string $motif=null): void {
        MouvementBudgetaire::create(['budget_exercice_id'=>$ligne->budget_exercice_id,'ligne_budgetaire_id'=>$ligne->id,'engagement_budgetaire_id'=>$engagement?->id,'realisation_budgetaire_id'=>$realisation?->id,'operation_uuid'=>(string)Str::uuid(),'type'=>$type,'montant'=>$montant,'monnaie'=>$ligne->budget->monnaie,'source_type'=>$source::class,'source_id'=>$source->id,'reference_document'=>$reference,'ancienne_situation'=>$avant,'nouvelle_situation'=>$apres,'utilisateur_id'=>Auth::id(),'date_mouvement'=>now(),'motif'=>$motif]);
    }
}
