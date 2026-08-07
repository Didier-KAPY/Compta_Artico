<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneBRC extends Model
{
    use HasFactory;

    protected $table = 'ligne_brcs';

    protected $fillable = ['brc_id', 'liste_des_comptes_id', 'libelle', 'montant'];

    protected $casts = ['montant' => 'decimal:2'];

    public function brc()
    {
        return $this->belongsTo(BRC::class, 'brc_id');
    }

    public function compte()
    {
        return $this->belongsTo(ListeDesComptes::class, 'liste_des_comptes_id');
    }
}
