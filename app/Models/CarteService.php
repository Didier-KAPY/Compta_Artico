<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarteService extends Model
{
    use HasFactory;

    protected $table = 'cartes_service';

    protected $fillable = [
        'user_id', 'numero', 'postnom', 'adresse', 'date_naissance',
        'sexe', 'date_delivrance', 'nom_signataire',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_delivrance' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
