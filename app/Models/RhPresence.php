<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RhPresence extends Model { use \Illuminate\Database\Eloquent\SoftDeletes; protected $table='rh_presences'; protected $guarded=[]; protected $casts=['date'=>'date']; public function employe(){return $this->belongsTo(Employe::class);} public function ancienUtilisateur(){return $this->belongsTo(User::class,'user_id');} }
