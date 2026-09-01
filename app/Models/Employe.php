<?php
namespace App\Models;
use Illuminate\Database\Eloquent\{Builder,Factories\HasFactory,Model,SoftDeletes};
class Employe extends Model {
 use HasFactory,SoftDeletes; protected $table='rh_employes'; protected $guarded=[];
 protected $casts=['date_naissance'=>'date','date_embauche'=>'date','date_depart'=>'date','expiration_piece_identite'=>'date'];
 public function entreprise(){return $this->belongsTo(Entreprise::class);} public function user(){return $this->belongsTo(User::class);} public function departement(){return $this->belongsTo(Departement::class);} public function service(){return $this->belongsTo(RhService::class,'service_id');} public function fonction(){return $this->belongsTo(Fonction::class);} public function superieur(){return $this->belongsTo(self::class,'superieur_id');} public function site(){return $this->belongsTo(RhSite::class,'site_id');} public function horaire(){return $this->belongsTo(RhHoraire::class,'horaire_id');}
 public function contrats(){return $this->hasMany(RhContrat::class);} public function presences(){return $this->hasMany(RhPresence::class);} public function conges(){return $this->hasMany(RhConge::class);} public function paies(){return $this->hasMany(RhPaie::class);} public function evaluations(){return $this->hasMany(RhEvaluation::class);} public function documents(){return $this->hasMany(RhDocument::class);}
 public function scopeSearch(Builder $q,?string $v):Builder { return $q->when($v,fn($q)=>$q->where(fn($q)=>$q->where('matricule','like',"%$v%")->orWhere('nom','like',"%$v%")->orWhere('prenom','like',"%$v%")->orWhere('telephone','like',"%$v%")->orWhere('email','like',"%$v%"))); }
}
