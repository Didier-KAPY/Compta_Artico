<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactureDetail extends Model
{
    protected $table = 'facture_details';

    protected $fillable = [
        'facture_id',
        'libelle',
        'quantite',
        'prix_unitaire',
        'montant_ligne'
    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}