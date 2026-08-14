<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeComptable extends Model
{
    protected $table = 'periodes_comptables';

    protected $fillable = [
        'type', 'date_debut', 'date_fin', 'statut', 'fermee_par', 'fermee_le',
        'reouverte_par', 'reouverte_le', 'motif_reouverture',
    ];

    protected $casts = [
        'date_debut' => 'date', 'date_fin' => 'date', 'fermee_le' => 'datetime', 'reouverte_le' => 'datetime',
    ];

    public function fermeur() { return $this->belongsTo(User::class, 'fermee_par'); }
    public function reouvreur() { return $this->belongsTo(User::class, 'reouverte_par'); }
}
