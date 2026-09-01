<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    protected $table = 'roles';
    protected $fillable = [
        'designation',
        'type',
        'observation'
    ];
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function rhPermissions(){ return $this->belongsToMany(RhPermission::class,'rh_permission_role','role_id','permission_id'); }
}
