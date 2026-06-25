<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListeDesComptes extends Model
{
    use HasFactory;

    protected $table = 'liste_des_comptes';

    protected $fillable = [
        'user_id',
        'compte',
        'designation',
        'nature',
        'observation',
    ];

    /**
     * Relation : un compte appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}