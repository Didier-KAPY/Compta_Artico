<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class RhRubriquePaie extends Model {protected $table='rh_rubriques_paie';protected $guarded=[];protected $casts=['valeur'=>'decimal:4','imposable'=>'boolean','soumis_cotisation'=>'boolean','actif'=>'boolean'];public function calculer(float $base,?float $saisie=null):float{if($saisie!==null)return round($saisie,2);return round($this->mode_calcul==='Taux'?$base*(float)$this->valeur/100:(float)$this->valeur,2);}}
