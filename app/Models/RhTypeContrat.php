<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class RhTypeContrat extends Model {protected $table='rh_types_contrats';protected $guarded=[];protected $casts=['date_fin_obligatoire'=>'boolean','actif'=>'boolean'];}
