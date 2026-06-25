<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtatBesoinLigne extends Model
{
    protected $fillable = [
        'etat_besoin_id',
        'designation',
        'quantite',
        'prix_unitaire',
        'montant'
    ];

    /**
     * Relation vers Etat de besoin
     */
    public function etatBesoin()
    {
        return $this->belongsTo(EtatBesoin::class);
    }
}