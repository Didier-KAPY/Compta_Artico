<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntreeCaisse extends Model
{
    use SoftDeletes;
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
        ,'motif_suppression', 'supprime_par', 'restaure_par', 'restaure_le',
        'origine', 'cloture_journaliere_id', 'genere_automatiquement_le',
    ];

    protected $casts = ['date' => 'date', 'date_validation' => 'datetime', 'restaure_le' => 'datetime', 'genere_automatiquement_le' => 'datetime'];

    /**
     * Utilisateur créateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    /**
     * Lignes de l'entrée de caisse
     */
    public function lignes()
    {
        return $this->hasMany(EntreeCaisseLigne::class, 'entree_caisse_id');
    }

    public function journaux()
    {
        return $this->hasMany(Journaux::class, 'entree_caisse_id');
    }

    public function clotureJournaliere()
    {
        return $this->belongsTo(ClotureJournaliere::class);
    }
}
