<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BRC extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'brcs';

    protected $fillable = [
        'user_id', 'journal_type_id', 'journal_id', 'reference', 'date',
        'monnaie', 'sens', 'total', 'piece_justificative', 'statut', 'valide_par', 'date_validation',
        'motif_suppression', 'supprime_par', 'restaure_par', 'restaure_le',
        'origine', 'cloture_journaliere_id', 'genere_automatiquement_le',
    ];

    protected $casts = [
        'date' => 'date',
        'total' => 'decimal:2',
        'date_validation' => 'datetime',
        'restaure_le' => 'datetime',
        'genere_automatiquement_le' => 'datetime',
    ];

    public function lignes()
    {
        return $this->hasMany(LigneBRC::class, 'brc_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function journalType()
    {
        return $this->belongsTo(JournalType::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journaux::class, 'journal_id');
    }

    public function journaux()
    {
        return $this->belongsToMany(Journaux::class, 'brc_journal', 'brc_id', 'journal_id');
    }

    public function clotureJournaliere()
    {
        return $this->belongsTo(ClotureJournaliere::class);
    }
}
