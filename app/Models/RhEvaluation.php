<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RhEvaluation extends Model { use \Illuminate\Database\Eloquent\SoftDeletes; protected $table='rh_evaluations'; protected $guarded=[]; public function employe(){return $this->belongsTo(Employe::class);} public function ancienUtilisateur(){return $this->belongsTo(User::class,'user_id');} public function evaluateur(){return $this->belongsTo(User::class,'evalue_par');} }
