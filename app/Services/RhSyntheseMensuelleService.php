<?php
namespace App\Services;
use App\Models\{Employe,RhPresence,RhSyntheseMensuelle};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RhSyntheseMensuelleService {
    public function generer(Employe $employe,int $annee,int $mois,int $auteur):RhSyntheseMensuelle {
        if($employe->statut!=='Actif') throw ValidationException::withMessages(['employe_id'=>'L’employé doit être actif.']);
        return DB::transaction(function()use($employe,$annee,$mois,$auteur){
            $debut=Carbon::create($annee,$mois,1)->startOfMonth();$fin=$debut->copy()->endOfMonth();
            $pointages=RhPresence::where('employe_id',$employe->id)->whereBetween('date',[$debut->toDateString(),$fin->toDateString()])->get();
            $ouvrables=0;for($jour=$debut->copy();$jour->lte($fin);$jour->addDay())if(!$jour->isWeekend())$ouvrables++;
            $presents=['Présent','Retard','Télétravail'];$nonRemunerees=['Absent','Absence non justifiée'];
            $donnees=['entreprise_id'=>$employe->entreprise_id,'employe_id'=>$employe->id,'annee'=>$annee,'mois'=>$mois,'jours_ouvrables_prevus'=>$ouvrables,'jours_presents'=>$pointages->whereIn('statut',$presents)->count(),'jours_conges_payes'=>$pointages->whereIn('statut',['Congé','Absence justifiée','Maladie','Permission'])->count(),'jours_mission'=>$pointages->where('statut','Mission')->count(),'jours_absence_non_remuneree'=>$pointages->whereIn('statut',$nonRemunerees)->count(),'nombre_retards'=>$pointages->where('retard_minutes','>',0)->count(),'retard_total_minutes'=>$pointages->sum('retard_minutes'),'heures_supplementaires_validees'=>$pointages->where('heures_supplementaires_approuvees',true)->sum('heures_supplementaires_validees'),'statut'=>'Générée','generee_par'=>$auteur,'generee_le'=>now()];
            $s=RhSyntheseMensuelle::where(['employe_id'=>$employe->id,'annee'=>$annee,'mois'=>$mois])->lockForUpdate()->first();
            if($s&&in_array($s->statut,['Validée','Verrouillée'],true))throw ValidationException::withMessages(['mois'=>'Une synthèse validée ou verrouillée ne peut pas être régénérée.']);
            $s?$s->update($donnees):$s=RhSyntheseMensuelle::create($donnees);return $s;
        });
    }
}
