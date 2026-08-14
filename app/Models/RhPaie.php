<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RhPaie extends Model { protected $table='rh_paies'; protected $guarded=[]; protected $casts=['salaire_base'=>'decimal:2','primes'=>'decimal:2','retenues'=>'decimal:2','date_paiement'=>'date']; public function employe(){return $this->belongsTo(User::class,'user_id');} public function getNetAttribute(){return (float)$this->salaire_base+(float)$this->primes-(float)$this->retenues;} }
