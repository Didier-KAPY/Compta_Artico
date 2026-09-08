<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RhSyntheseMensuelle extends Model {
    protected $table='rh_syntheses_mensuelles'; protected $guarded=[];
    protected $casts=['jours_conges_payes'=>'decimal:2','jours_mission'=>'decimal:2','jours_absence_non_remuneree'=>'decimal:2','heures_supplementaires_validees'=>'decimal:2','generee_le'=>'datetime','validee_le'=>'datetime','verrouillee_le'=>'datetime'];
    public function employe(){return $this->belongsTo(Employe::class);} public function periodePaie(){return $this->belongsTo(RhPeriodePaie::class);} public function paies(){return $this->hasMany(RhPaie::class,'synthese_mensuelle_id');}
}
