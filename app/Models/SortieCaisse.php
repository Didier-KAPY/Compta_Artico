<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SortieCaisse extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'numero',
        'date',
        'etat_besoin_id',
        'beneficiaire',
        'motif',
        'montant',
        'monnaie',
        'statut',
        'type', 
        'observation',
        'date_validation',
        'valide_par',
        'motif_suppression', 'supprime_par', 'restaure_par', 'restaure_le',
        'origine', 'cloture_journaliere_id', 'genere_automatiquement_le',
    ];

    protected $casts = ['date' => 'date', 'date_validation' => 'datetime', 'restaure_le' => 'datetime', 'genere_automatiquement_le' => 'datetime'];

        public function etatBesoin()
    {
        return $this->belongsTo(EtatBesoin::class, 'etat_besoin_id');
    }
     
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }
    public function lignes()
    {
        return $this->etatBesoin
            ? $this->etatBesoin->lignes()
            : collect();
    }

    public function journaux()
    {
        return $this->hasMany(Journaux::class, 'sortie_caisse_id');
    }

    public function lignesCloture()
    {
        return $this->hasMany(SortieCaisseLigne::class, 'sortie_caisse_id');
    }

    public function clotureJournaliere()
    {
        return $this->belongsTo(ClotureJournaliere::class);
    }
}
