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
        'user_id',
        'nom_entreprise',
        'slogan',
        'adresse',
        'forme_juridique',
        'numero_identification_fiscal',
        'telephone',
        'logo',
        'cachet',
        'monnaie_budgetaire',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function employes(){ return $this->hasMany(Employe::class); }
    
}
