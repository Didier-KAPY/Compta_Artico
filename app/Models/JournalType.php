<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalType extends Model
{
    use HasFactory;

    protected $table = 'journal_types';

    protected $fillable = [
        'user_id',
        'liste_des_comptes_id',
    ];

    /**
     * Relation : appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : lié à un compte
     */
    public function compte()
    {
        return $this->belongsTo(ListeDesComptes::class, 'liste_des_comptes_id');
    }
}