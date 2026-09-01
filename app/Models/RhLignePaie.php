<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class RhLignePaie extends Model {protected $table='rh_lignes_paie';protected $guarded=[];protected $casts=['base'=>'decimal:2','taux'=>'decimal:4','montant'=>'decimal:2'];public function rubrique(){return $this->belongsTo(RhRubriquePaie::class);}public function paie(){return $this->belongsTo(RhPaie::class);}}
