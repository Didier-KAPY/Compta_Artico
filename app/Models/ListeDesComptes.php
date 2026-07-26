<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\EcritureComptable;

class ListeDesComptes extends Model
{
    use HasFactory;

    protected $table = 'liste_des_comptes';

    protected $fillable = [
        'user_id',
        'compte',
        'designation',
        'nature',
        'observation',
    ];

    public function journaux()
{
return $this->hasMany(
Journaux::class,
'liste_des_comptes_id'
);
}
public function getClasseAttribute()
{
return substr($this->compte,0,1);
}
public function user()
{
    return $this->belongsTo(
        User::class,
        'user_id'
    );
}
}