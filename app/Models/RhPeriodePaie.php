<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class RhPeriodePaie extends Model{protected $table='rh_periodes_paie';protected $guarded=[];protected $casts=['date_debut'=>'date','date_fin'=>'date','validee_le'=>'datetime'];public function syntheses(){return $this->hasMany(RhSyntheseMensuelle::class,'periode_paie_id');}public function paies(){return $this->hasMany(RhPaie::class,'periode_paie_id');}}
