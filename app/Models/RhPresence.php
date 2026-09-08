<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RhPresence extends Model { use \Illuminate\Database\Eloquent\SoftDeletes; protected $table='rh_presences'; protected $guarded=[]; protected $casts=['date'=>'date','heures_supplementaires_approuvees'=>'boolean','heures_supplementaires_validees'=>'decimal:2','valeurs_avant_correction'=>'array','valide_le'=>'datetime']; public function employe(){return $this->belongsTo(Employe::class);} public function ancienUtilisateur(){return $this->belongsTo(User::class,'user_id');} public function pointePar(){return $this->belongsTo(User::class,'pointe_par');} public function historiques(){return $this->morphMany(RhWorkflowHistorique::class,'workflowable');} }
