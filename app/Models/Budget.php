<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = ['departement_id', 'liste_des_comptes_id', 'date_debut', 'date_fin', 'montant_prevu', 'monnaie', 'cree_par'];
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date', 'montant_prevu' => 'decimal:2'];
    public function departement() { return $this->belongsTo(Departement::class); }
    public function compte() { return $this->belongsTo(ListeDesComptes::class, 'liste_des_comptes_id'); }
    public function createur() { return $this->belongsTo(User::class, 'cree_par'); }
}
