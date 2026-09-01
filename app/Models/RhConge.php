<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RhConge extends Model { use \Illuminate\Database\Eloquent\SoftDeletes; protected $table='rh_conges'; protected $guarded=[]; protected $casts=['date_debut'=>'date','date_fin'=>'date','valide_le'=>'datetime']; public function employe(){return $this->belongsTo(Employe::class);} public function ancienUtilisateur(){return $this->belongsTo(User::class,'user_id');} public function validateur(){return $this->belongsTo(User::class,'valide_par');} }
