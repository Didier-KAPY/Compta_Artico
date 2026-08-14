<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EtatBesoin extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'departement_id',
        'ligne_budgetaire_id',
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
        'motif_suppression', 'supprime_par', 'restaure_par', 'restaure_le',
    ];

    protected $casts = ['date' => 'date', 'date_validation' => 'datetime', 'restaure_le' => 'datetime'];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
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
    public function ligneBudgetaire(){ return $this->belongsTo(LigneBudgetaire::class); }
    public function engagementBudgetaire(){ return $this->hasOne(EngagementBudgetaire::class); }
}
