<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtatBesoin extends Model
{
    protected $fillable = [
        'user_id',
        'departement_id',
        'numero',
        'date',
        'service',
        'demandeur',
        'motif',
        'montant_estime',
        'monnaie',
        'statut',
        'observation',
        'valide_par',
        'date_validation',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    /**
     * Relation avec les lignes de l'état de besoin
     */
    public function lignes()
    {
        return $this->hasMany(
            EtatBesoinLigne::class,
            'etat_besoin_id'
        );
    }

    public function sortieCaisses()
    {
        return $this->hasMany(SortieCaisse::class, 'etat_besoin_id');
    }
}
