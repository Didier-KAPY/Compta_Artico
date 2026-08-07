<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SortieCaisseLigne extends Model
{
    protected $fillable = ['sortie_caisse_id', 'journal_id', 'designation', 'quantite', 'prix_unitaire', 'montant'];
    protected $casts = ['quantite' => 'decimal:2', 'prix_unitaire' => 'decimal:2', 'montant' => 'decimal:2'];
    public function sortie() { return $this->belongsTo(SortieCaisse::class, 'sortie_caisse_id'); }
    public function journal() { return $this->belongsTo(Journaux::class); }
}
