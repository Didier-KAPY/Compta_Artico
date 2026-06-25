<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SortieCaisse extends Model
{
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
    ];

        public function etatBesoin()
    {
        return $this->belongsTo(EtatBesoin::class, 'etat_besoin_id');
    }
     
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function lignes()
    {
        return $this->etatBesoin
            ? $this->etatBesoin->lignes()
            : collect();
    }
}
