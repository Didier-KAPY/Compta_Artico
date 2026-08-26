<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BilanInitial extends Model
{
    protected $fillable = [
        'user_id',
        'libelle',
        'date_debut',
        'date_fin',
        'total_actif',
        'total_passif',
        'ecart',
        'donnees',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'total_actif' => 'decimal:2',
            'total_passif' => 'decimal:2',
            'ecart' => 'decimal:2',
            'donnees' => 'array',
        ];
    }
}