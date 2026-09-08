<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RhWorkflowHistorique extends Model {
    protected $table='rh_workflow_historiques'; protected $guarded=[];
    protected $casts=['ancienne_valeur'=>'array','nouvelle_valeur'=>'array'];
    public function workflowable(){return $this->morphTo();}
}
