<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RhPresence extends Model { protected $table='rh_presences'; protected $guarded=[]; protected $casts=['date'=>'date']; public function employe(){return $this->belongsTo(User::class,'user_id');} }
