<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $fillable = [
        'facture_id',
        'liste_des_comptes_id',
        'id_user',
        'montant',
        'mode_paiement',
        'date_paiement'
    ];

    protected $casts = [
        'date_paiement' => 'datetime',
    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }

    public function compteTresorerie()
    {
        return $this->belongsTo(
            ListeDesCompte::class,
            'liste_des_comptes_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}