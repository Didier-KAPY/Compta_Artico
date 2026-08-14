<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TauxDeChange extends Model
{
    use HasFactory;

    protected $table = 'taux_de_changes';

    protected $fillable = [
        'user_id',
        'taux_de_change',
        'entreprise_id', 'devise_source', 'devise_cible', 'date_taux',
    ];
    protected $casts = ['date_taux' => 'date', 'taux_de_change' => 'decimal:2'];

    /**
     * Relation : appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
