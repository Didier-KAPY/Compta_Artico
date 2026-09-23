<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\EcritureComptable;

class ListeDesComptes extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $account) {
            if (!$account->entreprise_id && auth()->user() && Entreprise::exists()) $account->entreprise_id = app(\App\Services\CurrentEntreprise::class)->for()->id;
        });
    }
    use HasFactory;

    protected $table = 'liste_des_comptes';

    protected $fillable = [
        'entreprise_id',
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
