<?php
namespace App\Services;
use App\Models\RhWorkflowHistorique;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RhWorkflowService {
    public function transition(Model $model, string $nouveauStatut, array $transitions, int $auteur, ?string $motif=null, array $changements=[]): Model {
        $ancien=(string)$model->statut;
        if(!in_array($nouveauStatut,$transitions[$ancien]??[],true)) throw ValidationException::withMessages(['statut'=>"Transition interdite : {$ancien} vers {$nouveauStatut}."]);
        $avant=$model->toArray();
        $model->update($changements+['statut'=>$nouveauStatut]);
        RhWorkflowHistorique::create(['entreprise_id'=>$model->entreprise_id,'workflowable_type'=>$model::class,'workflowable_id'=>$model->getKey(),'action'=>'changement_statut','ancien_statut'=>$ancien,'nouveau_statut'=>$nouveauStatut,'motif'=>$motif,'ancienne_valeur'=>$avant,'nouvelle_valeur'=>$model->fresh()->toArray(),'effectue_par'=>$auteur]);
        return $model;
    }

    public function trace(Model $model,string $action,int $auteur,?string $motif=null,?array $avant=null,?array $apres=null):void {
        RhWorkflowHistorique::create(['entreprise_id'=>$model->entreprise_id,'workflowable_type'=>$model::class,'workflowable_id'=>$model->getKey(),'action'=>$action,'ancien_statut'=>$avant['statut']??$model->statut,'nouveau_statut'=>$apres['statut']??$model->statut,'motif'=>$motif,'ancienne_valeur'=>$avant,'nouvelle_valeur'=>$apres,'effectue_par'=>$auteur]);
    }
}
