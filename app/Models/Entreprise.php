<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entreprise extends Model
{
    use HasFactory;

    // Nom de la table (optionnel si tu respectes la convention Laravel)
    protected $table = 'entreprises';

    // Champs assignables en masse
    protected $fillable = [
        'nom_entreprise',
        'adresse',
        'forme_juridique',
        'numero_identification_fiscal',
        'telephone',
        'logo',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}