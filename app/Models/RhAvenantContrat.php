<?php
namespace App\Models; use Illuminate\Database\Eloquent\{Model,SoftDeletes};
class RhAvenantContrat extends Model {use SoftDeletes;protected $table='rh_avenants_contrats';protected $guarded=[];protected $casts=['date_effet'=>'date','anciennes_valeurs'=>'array','nouvelles_valeurs'=>'array','valide_le'=>'datetime'];public function contrat(){return $this->belongsTo(RhContrat::class);}}
