<?php

namespace App\Services;

use App\Models\BRC;
use App\Models\ClotureJournaliere;
use App\Models\ClotureJournaliereJournal;
use App\Models\EntreeCaisse;
use App\Models\Entreprise;
use App\Models\Journaux;
use App\Models\SortieCaisse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClotureJournaliereService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private AuditLogService $audit,
    ) {}

    public function simulation(string $date): array
    {
        $eligibles = $this->queryEligibles($date)->get();
        $rejetes = Journaux::whereDate('date', $date)->where('statut', 'Rejeté')->count();
        $groupes = $eligibles->groupBy(fn (Journaux $journal) => implode('|', [
            $this->categorie($journal), $this->tresorerie($journal), $journal->monnaie,
        ]));

        return [
            'date' => $date,
            'journaux' => $eligibles,
            'groupes' => $groupes,
            'rejetes' => $rejetes,
            'totaux' => $this->totaux($eligibles),
        ];
    }

    public function cloturer(string $date, bool $complementaire = false, ?string $motif = null): ClotureJournaliere
    {
        return DB::transaction(function () use ($date, $complementaire, $motif) {
            $entrepriseId = (int) (Entreprise::value('id') ?? 0);
            $precedentes = ClotureJournaliere::where('entreprise_id', $entrepriseId)
                ->whereDate('date_comptable', $date)->lockForUpdate()->get();

            if ($precedentes->whereIn('statut', ['cloturee', 'verifiee'])->isNotEmpty() && ! $complementaire) {
                throw ValidationException::withMessages(['date' => 'Cette journée est déjà clôturée. Lancez une clôture complémentaire.']);
            }
            if ($complementaire && ($motif === null || mb_strlen(trim($motif)) < 10)) {
                throw ValidationException::withMessages(['motif' => 'Le motif de la clôture complémentaire doit contenir au moins 10 caractères.']);
            }

            $journaux = $this->queryEligibles($date)->lockForUpdate()->get();
            if ($journaux->isEmpty()) {
                throw ValidationException::withMessages(['date' => 'Aucun journal non regroupé et éligible pour cette journée.']);
            }

            $revision = ((int) $precedentes->max('revision')) + 1;
            $totaux = $this->totaux($journaux);
            $totaux['nombre_journaux_rejetes'] = Journaux::whereDate('date', $date)->where('statut', 'Rejeté')->count();
            $cloture = ClotureJournaliere::create($totaux + [
                'entreprise_id' => $entrepriseId,
                'numero_cloture' => $this->numbers->next('CLJ', $date, null, $entrepriseId),
                'date_comptable' => $date,
                'revision' => $revision,
                'type_cloture' => $complementaire ? 'complementaire' : 'principale',
                'statut' => 'en_cours',
                'ouverte_par' => Auth::id(),
                'cloturee_par' => Auth::id(),
                'date_cloture' => now(),
                'motif_complement' => $complementaire ? trim((string) $motif) : null,
            ]);

            $journaux->each->update(['statut_regroupement' => 'reserve']);
            $journaux->groupBy(fn (Journaux $journal) => implode('|', [
                $this->categorie($journal), $this->tresorerie($journal), $journal->monnaie,
            ]))->each(function (Collection $groupe) use ($cloture, $date) {
                $categorie = $this->categorie($groupe->first());
                match ($categorie) {
                    'entree' => $this->creerEntree($cloture, $groupe, $date),
                    'sortie' => $this->creerSortie($cloture, $groupe, $date),
                    'brc' => $this->creerBrc($cloture, $groupe, $date),
                };
            });

            $cloture->update(['statut' => 'cloturee']);
            $this->audit->record('cloture_journaliere', $cloture, $cloture->numero_cloture, $motif,
                null, $cloture->fresh()->attributesToArray(), [], request());

            return $cloture->fresh(['entrees', 'sorties', 'brcs', 'journaux']);
        }, 5);
    }

    public function verifier(ClotureJournaliere $cloture): ClotureJournaliere
    {
        return DB::transaction(function () use ($cloture) {
            $locked = ClotureJournaliere::lockForUpdate()->findOrFail($cloture->id);
            if ($locked->statut !== 'cloturee') {
                throw ValidationException::withMessages(['statut' => 'Seule une clôture terminée peut être vérifiée.']);
            }
            $journaux = $locked->journaux()->get();
            if ($locked->entrees()->where('statut', '!=', 'Validé')->exists()
                || $locked->sorties()->where('statut', '!=', 'Validé')->exists()
                || $locked->brcs()->where('statut', '!=', 'Validé')->exists()
                || $journaux->contains(fn ($journal) => $journal->statut !== 'Validé' || ! $journal->ecritures()->exists())) {
                throw ValidationException::withMessages(['statut' => 'Tous les bons, journaux et écritures doivent être générés avant la vérification.']);
            }
            $attendus = $this->totaux($journaux);
            foreach (array_diff(array_keys($attendus), ['total_ecritures', 'nombre_journaux_rejetes']) as $champ) {
                if ((float) $locked->{$champ} !== (float) $attendus[$champ]) {
                    throw ValidationException::withMessages(['totaux' => "Écart détecté dans {$champ}."]);
                }
            }
            $locked->update([
                'total_ecritures' => $journaux->sum(fn ($journal) => $journal->ecritures()->count()),
                'est_verifiee' => true, 'verifiee_par' => Auth::id(), 'verifiee_le' => now(), 'statut' => 'verifiee',
            ]);
            $this->audit->record('verification_cloture', $locked, $locked->numero_cloture, null,
                $cloture->attributesToArray(), $locked->fresh()->attributesToArray(), [], request());
            return $locked->fresh();
        });
    }

    private function queryEligibles(string $date)
    {
        return Journaux::with(['journalType', 'ecritures'])
            ->whereDate('date', $date)->where('statut_regroupement', 'non_regroupe')
            ->whereNull('entree_caisse_id')->whereNull('sortie_caisse_id')
            ->where('statut', '!=', 'Rejeté')->doesntHave('brcs');
    }

    private function categorie(Journaux $journal): string
    {
        return match ($journal->type) {
            'recette', 'vente' => 'entree',
            'depense', 'achat' => 'sortie',
            default => 'brc',
        };
    }

    private function tresorerie(Journaux $journal): string
    {
        $nature = mb_strtolower((string) $journal->journalType?->nature);
        return match (true) {
            str_contains($nature, 'banque'), $journal->mode_paiement === 'banque' => 'banque',
            str_contains($nature, 'mobile'), $journal->mode_paiement === 'mobile_money' => 'mobile_money',
            $this->categorie($journal) === 'brc' => 'od',
            default => 'caisse',
        };
    }

    private function montant(Journaux $journal): float
    {
        if ($this->categorie($journal) === 'entree') {
            return (float) ($journal->monnaie === 'USD' ? $journal->entrees_usd : $journal->entrees_cdf) ?: (float) $journal->montant_ttc;
        }
        if ($this->categorie($journal) === 'sortie') {
            return (float) ($journal->monnaie === 'USD' ? $journal->sorties_usd : $journal->sorties_cdf) ?: (float) $journal->montant_ttc;
        }
        return (float) $journal->montant_ttc;
    }

    private function totaux(Collection $journaux): array
    {
        $somme = fn (string $categorie, string $monnaie) => $journaux
            ->filter(fn ($j) => $this->categorie($j) === $categorie && $j->monnaie === $monnaie)
            ->sum(fn ($j) => $this->montant($j));
        return [
            'total_recettes_cdf' => $somme('entree', 'CDF'), 'total_recettes_usd' => $somme('entree', 'USD'),
            'total_depenses_cdf' => $somme('sortie', 'CDF'), 'total_depenses_usd' => $somme('sortie', 'USD'),
            'total_od_cdf' => $somme('brc', 'CDF'), 'total_od_usd' => $somme('brc', 'USD'),
            'total_journaux' => $journaux->count(),
            'total_ecritures' => $journaux->sum(fn ($journal) => $journal->ecritures->count()),
            'nombre_journaux_rejetes' => 0,
        ];
    }

    private function creerEntree(ClotureJournaliere $cloture, Collection $journaux, string $date): void
    {
        $premier = $journaux->first(); $type = $this->tresorerie($premier); $total = $journaux->sum(fn ($j) => $this->montant($j));
        $bon = EntreeCaisse::create([
            'user_id' => Auth::id(), 'numero' => $this->numbers->next('BEC', $date, $type), 'date' => $date,
            'motif' => 'Regroupement quotidien '.$cloture->numero_cloture, 'type' => $this->libelleTresorerie($type),
            'montant' => $total, 'monnaie' => $premier->monnaie, 'statut' => 'En attente',
            'origine' => 'cloture', 'cloture_journaliere_id' => $cloture->id, 'genere_automatiquement_le' => now(),
        ]);
        foreach ($journaux as $journal) {
            $bon->lignes()->create(['designation' => $journal->reference.' — '.$journal->description, 'quantite' => 1, 'prix_unitaire' => $this->montant($journal), 'montant' => $this->montant($journal)]);
            $this->rattacher($cloture, $journal, 'entree', $type, ['entree_caisse_id' => $bon->id]);
            $journal->update(['entree_caisse_id' => $bon->id]);
        }
    }

    private function creerSortie(ClotureJournaliere $cloture, Collection $journaux, string $date): void
    {
        $premier = $journaux->first(); $type = $this->tresorerie($premier); $total = $journaux->sum(fn ($j) => $this->montant($j));
        $bon = SortieCaisse::create([
            'user_id' => Auth::id(), 'numero' => $this->numbers->next('BSC', $date, $type), 'date' => $date,
            'beneficiaire' => 'Bénéficiaires multiples', 'motif' => 'Regroupement quotidien '.$cloture->numero_cloture,
            'type' => $this->libelleTresorerie($type), 'montant' => $total, 'monnaie' => $premier->monnaie,
            'observation' => 'Généré automatiquement par la clôture.', 'statut' => 'En attente',
            'origine' => 'cloture', 'cloture_journaliere_id' => $cloture->id, 'genere_automatiquement_le' => now(),
        ]);
        foreach ($journaux as $journal) {
            $bon->lignesCloture()->create(['journal_id' => $journal->id, 'designation' => $journal->reference.' — '.$journal->description, 'quantite' => 1, 'prix_unitaire' => $this->montant($journal), 'montant' => $this->montant($journal)]);
            $this->rattacher($cloture, $journal, 'sortie', $type, ['sortie_caisse_id' => $bon->id]);
            $journal->update(['sortie_caisse_id' => $bon->id]);
        }
    }

    private function creerBrc(ClotureJournaliere $cloture, Collection $journaux, string $date): void
    {
        $premier = $journaux->first(); $total = $journaux->sum(fn ($j) => $this->montant($j));
        $brc = BRC::create([
            'user_id' => Auth::id(), 'journal_type_id' => $premier->journal_type_id,
            'reference' => $this->numbers->next('BRC', $date, 'od'), 'date' => $date,
            'monnaie' => $premier->monnaie, 'sens' => 'debit', 'total' => $total, 'statut' => 'En attente',
            'origine' => 'cloture', 'cloture_journaliere_id' => $cloture->id, 'genere_automatiquement_le' => now(),
        ]);
        foreach ($journaux as $journal) {
            if ($journal->liste_des_comptes_id) {
                $brc->lignes()->create(['liste_des_comptes_id' => $journal->liste_des_comptes_id, 'libelle' => $journal->reference.' — '.$journal->description, 'montant' => $this->montant($journal)]);
            }
            $brc->journaux()->attach($journal->id);
            $this->rattacher($cloture, $journal, 'brc', 'od', ['brc_id' => $brc->id]);
        }
    }

    private function rattacher(ClotureJournaliere $cloture, Journaux $journal, string $categorie, string $tresorerie, array $document): void
    {
        ClotureJournaliereJournal::create($document + [
            'cloture_journaliere_id' => $cloture->id, 'journal_id' => $journal->id,
            'categorie_document' => $categorie, 'type_tresorerie' => $tresorerie, 'regroupe_le' => now(),
        ]);
        $journal->update(['statut_regroupement' => 'regroupe', 'cloture_journaliere_id' => $cloture->id, 'regroupe_le' => now()]);
    }

    private function libelleTresorerie(string $type): string
    {
        return match ($type) { 'banque' => 'Banque', 'mobile_money' => 'Mobile Money', default => 'Caisse' };
    }
}
