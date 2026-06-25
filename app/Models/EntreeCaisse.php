<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntreeCaisse extends Model
{
    protected $fillable = [
        'user_id',
        'numero',
        'date',
        'motif',
        'type',
        'montant',
        'monnaie',
        'statut',
        'observation',
        'date_validation',
        'valide_par'
    ];

    /**
     * Utilisateur créateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lignes de l'entrée de caisse
     */
    public function lignes()
    {
        return $this->hasMany(EntreeCaisseLigne::class, 'entree_caisse_id');
    }
}