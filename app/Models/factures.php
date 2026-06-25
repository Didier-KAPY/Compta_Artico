<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    protected $fillable = [
        'numero_facture',
        'nom_client',
        'contact_client',
        'id_user',
        'montant_total',
        'montant_paye',
        'reste_a_payer',
        'numero_compte',
        'statut',
        'date_facture'
    ];

    public function details()
    {
        return $this->hasMany(FactureDetail::class);
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}