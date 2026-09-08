<?php
namespace App\Services;
use App\Models\{Employe,RhContrat,RhPaie,RhPeriodePaie,RhSyntheseMensuelle};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RhPaieWorkflowService {
    public function calculer(Employe $e,int $annee,int $mois,bool $appliquerAbsence,?string $motif,int $auteur,array $extras=[]):RhPaie {
        if($e->statut!=='Actif')throw ValidationException::withMessages(['employe_id'=>'L’employé doit être actif.']);
        $debut=sprintf('%04d-%02d-01',$annee,$mois);$fin=date('Y-m-t',strtotime($debut));
        $contrat=RhContrat::where('employe_id',$e->id)->where('statut','Actif')->whereDate('date_debut','<=',$fin)->where(fn($q)=>$q->whereNull('date_fin')->orWhereDate('date_fin','>=',$debut))->latest('date_debut')->first();
        if(!$contrat)throw ValidationException::withMessages(['contrat'=>'Aucun contrat actif applicable à cette période.']);
        $avenants=$contrat->avenants()->where('statut','Validé')->whereDate('date_effet','<=',$fin)->orderBy('date_effet')->get();foreach($avenants as $avenant)$contrat->forceFill($avenant->nouvelles_valeurs??[]);
        $periode=RhPeriodePaie::where(['entreprise_id'=>$e->entreprise_id,'annee'=>$annee,'mois'=>$mois])->whereIn('statut',['Ouverte','En préparation'])->first();
        if(!$periode)throw ValidationException::withMessages(['periode'=>'La période de paie doit être ouverte ou en préparation.']);
        $s=RhSyntheseMensuelle::where(['employe_id'=>$e->id,'annee'=>$annee,'mois'=>$mois])->whereIn('statut',['Validée','Verrouillée'])->first();
        if(!$s)throw ValidationException::withMessages(['synthese'=>'La synthèse mensuelle doit être validée.']);
        if(!$appliquerAbsence&&blank($motif))throw ValidationException::withMessages(['motif_non_retenue_absence'=>'Le motif est obligatoire lorsque la retenue d’absence n’est pas appliquée.']);
        return DB::transaction(function()use($e,$annee,$mois,$contrat,$periode,$s,$appliquerAbsence,$motif,$auteur,$extras){
            $base=$contrat->mode_remuneration==='Journalier'?(float)$contrat->salaire_base*(float)$s->jours_presents:($contrat->mode_remuneration==='Horaire'?(float)$contrat->salaire_base*(float)($s->heures_travaillees??0):(float)$contrat->salaire_base);
            $journalier=round($base/max(1,(float)$contrat->jours_reference),2);$retenue=round($journalier*(float)$s->jours_absence_non_remuneree,2);$appliquerAbsence=$appliquerAbsence&&(bool)$contrat->regle_retenue_absence;
            $tauxHoraire=$base/max(1,(float)$contrat->heures_reference);$montantHs=$contrat->regle_heures_supplementaires?round($tauxHoraire*(float)$s->heures_supplementaires_validees*(float)($extras['coefficient_heures_supplementaires']??1.25),2):0;
            $data=['entreprise_id'=>$e->entreprise_id,'employe_id'=>$e->id,'user_id'=>$e->user_id,'contrat_id'=>$contrat->id,'synthese_mensuelle_id'=>$s->id,'annee'=>$annee,'mois'=>$mois,'salaire_base'=>$contrat->salaire_base,'primes'=>(float)$contrat->primes_fixes+(float)($extras['primes']??0),'retenues'=>$appliquerAbsence?$retenue:0,'appliquer_retenues'=>(bool)($extras['appliquer_retenues']??true),'monnaie'=>$contrat->devise,'statut'=>'Calculée','retenue_absence_theorique'=>$retenue,'appliquer_retenue_absence'=>$appliquerAbsence,'motif_non_retenue_absence'=>$appliquerAbsence?null:$motif,'decision_retenue_par'=>$auteur,'decision_retenue_le'=>now(),'heures_supplementaires'=>$s->heures_supplementaires_validees,'montant_heures_supplementaires'=>$montantHs,'cree_par'=>$auteur];
            $data['instantane_calcul']=['contrat'=>$contrat->only(['id','numero','type','salaire_base','devise','mode_remuneration','primes_fixes','indemnites_fixes','jours_reference','heures_reference']),'synthese'=>$s->only(['id','annee','mois','jours_ouvrables_prevus','jours_presents','jours_conges_payes','jours_mission','jours_absence_non_remuneree','retard_total_minutes','heures_supplementaires_validees']),'decision_retenue'=>['appliquee'=>$appliquerAbsence,'montant_theorique'=>$retenue,'motif'=>$motif,'auteur'=>$auteur,'date'=>now()->toIso8601String()]];
            $data['salaire_base']=$base;$data['primes']=(float)$contrat->primes_fixes+(float)$contrat->indemnites_fixes+(float)$contrat->prime_fonction+(float)$contrat->prime_transport+(float)$contrat->prime_logement+(float)$contrat->prime_responsabilite+(float)($extras['primes']??0);$data['retenues']=$appliquerAbsence?$retenue:0;$data['appliquer_retenue_absence']=$appliquerAbsence;
            $data['periode_paie_id']=$periode->id;
            $p=RhPaie::where(['employe_id'=>$e->id,'annee'=>$annee,'mois'=>$mois])->lockForUpdate()->first();if($p&&$p->statut!=='Brouillon')throw ValidationException::withMessages(['mois'=>'Cette paie a déjà été calculée.']);$p?$p->update($data):$p=RhPaie::create($data);$s->update(['statut'=>'Verrouillée','verrouillee_le'=>now()]);return $p;
        });
    }
}
