<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class RhService extends Model { protected $table='rh_services'; protected $guarded=[]; public function departement(){return $this->belongsTo(Departement::class);} public function responsable(){return $this->belongsTo(Employe::class,'responsable_employe_id');} }
