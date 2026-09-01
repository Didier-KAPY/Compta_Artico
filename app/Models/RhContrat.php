<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RhContrat extends Model { use \Illuminate\Database\Eloquent\SoftDeletes; protected $table='rh_contrats'; protected $guarded=[]; protected $casts=['date_debut'=>'date','date_fin'=>'date','salaire_base'=>'decimal:2','valide_le'=>'datetime']; public function employe(){return $this->belongsTo(Employe::class);} public function ancienUtilisateur(){return $this->belongsTo(User::class,'user_id');} public function typeContrat(){return $this->belongsTo(RhTypeContrat::class);} public function avenants(){return $this->hasMany(RhAvenantContrat::class,'contrat_id');} }
