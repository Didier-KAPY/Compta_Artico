<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RhContrat extends Model { protected $table='rh_contrats'; protected $guarded=[]; protected $casts=['date_debut'=>'date','date_fin'=>'date','salaire_base'=>'decimal:2']; public function employe(){return $this->belongsTo(User::class,'user_id');} }
