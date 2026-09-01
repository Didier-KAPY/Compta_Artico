<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class RhHoraire extends Model {protected $table='rh_horaires';protected $guarded=[];protected $casts=['heures_normales'=>'decimal:2','par_defaut'=>'boolean','actif'=>'boolean'];}
