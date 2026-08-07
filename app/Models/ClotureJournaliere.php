<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClotureJournaliere extends Model
{
    protected $table = 'clotures_journalieres';

    protected $fillable = [
        'entreprise_id', 'numero_cloture', 'date_comptable', 'revision', 'type_cloture', 'statut',
        'total_recettes_cdf', 'total_recettes_usd', 'total_depenses_cdf', 'total_depenses_usd',
        'total_od_cdf', 'total_od_usd', 'total_journaux', 'total_ecritures',
        'nombre_journaux_rejetes', 'est_verifiee', 'ouverte_par', 'cloturee_par', 'verifiee_par',
        'date_cloture', 'verifiee_le', 'motif_complement', 'motif_reouverture',
    ];

    protected $casts = [
        'date_comptable' => 'date', 'date_cloture' => 'datetime', 'verifiee_le' => 'datetime',
        'est_verifiee' => 'boolean',
    ];

    public function entrees() { return $this->hasMany(EntreeCaisse::class); }
    public function sorties() { return $this->hasMany(SortieCaisse::class); }
    public function brcs() { return $this->hasMany(BRC::class); }
    public function rattachements() { return $this->hasMany(ClotureJournaliereJournal::class); }
    public function journaux() { return $this->belongsToMany(Journaux::class, 'cloture_journaliere_journaux', 'cloture_journaliere_id', 'journal_id'); }
    public function clotureur() { return $this->belongsTo(User::class, 'cloturee_par'); }
    public function verificateur() { return $this->belongsTo(User::class, 'verifiee_par'); }
}
