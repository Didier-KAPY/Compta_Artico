<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClotureJournaliereJournal extends Model
{
    protected $table = 'cloture_journaliere_journaux';

    protected $fillable = [
        'cloture_journaliere_id', 'journal_id', 'categorie_document', 'type_tresorerie',
        'entree_caisse_id', 'sortie_caisse_id', 'brc_id', 'regroupe_le',
    ];
    protected $casts = ['regroupe_le' => 'datetime'];
    public function cloture() { return $this->belongsTo(ClotureJournaliere::class, 'cloture_journaliere_id'); }
    public function journal() { return $this->belongsTo(Journaux::class, 'journal_id'); }
    public function entree() { return $this->belongsTo(EntreeCaisse::class, 'entree_caisse_id'); }
    public function sortie() { return $this->belongsTo(SortieCaisse::class, 'sortie_caisse_id'); }
    public function brc() { return $this->belongsTo(BRC::class); }
}
