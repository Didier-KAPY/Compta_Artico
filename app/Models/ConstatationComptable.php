<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstatationComptable extends Model
{
    protected $table = 'constatations_comptables';
    protected $guarded = [];
    protected $casts = ['date'=>'date', 'instantane_source'=>'array', 'montant_cdf'=>'decimal:2', 'montant_usd'=>'decimal:2', 'taux_change'=>'decimal:8'];

    public function source() { return $this->belongsTo(EcritureComptable::class, 'source_ecriture_id'); }
    public function journalReglement() { return $this->belongsTo(Journaux::class, 'reglement_journal_id'); }
    public function entreprise() { return $this->belongsTo(Entreprise::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function compteLiaison() { return $this->belongsTo(ListeDesComptes::class, 'compte_liaison_id'); }
    public function lignes() { return $this->hasMany(EcritureComptable::class, 'constatation_id')->where('role_constatation', 'constatation')->orderByRaw('CASE WHEN debit_cdf > 0 THEN 0 ELSE 1 END')->orderBy('id'); }
    public function reglement() { return $this->hasMany(EcritureComptable::class, 'constatation_id')->where('role_constatation', 'reglement')->orderByRaw('CASE WHEN debit_cdf > 0 THEN 0 ELSE 1 END')->orderBy('id'); }
}
