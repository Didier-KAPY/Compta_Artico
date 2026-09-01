<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class RhPermission extends Model { protected $table='rh_permissions'; protected $guarded=[]; public function roles(){return $this->belongsToMany(Role::class,'rh_permission_role','permission_id','role_id');} }
