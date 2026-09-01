<?php
namespace App\Models; use Illuminate\Database\Eloquent\{Model,SoftDeletes};
class RhDocument extends Model { use SoftDeletes; protected $table='rh_documents'; protected $guarded=[]; protected $casts=['date_expiration'=>'date']; public function employe(){return $this->belongsTo(Employe::class);} public function documentable(){return $this->morphTo();} }
