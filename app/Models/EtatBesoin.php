<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EtatBesoin extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'departement_id',
        'ligne_budgetaire_id',
        'numero',
        'date',
        'service',
        'demandeur',
        'motif',
        'montant_estime',
        'monnaie',
        'statut',
        'observation',
        'valide_par',
        'date_validation',
        'piece_justificative',
        'pieces_justificatives',
        'piece_justificative_nom',
        'motif_suppression', 'supprime_par', 'restaure_par', 'restaure_le',
    ];

    protected $casts = ['pieces_justificatives' => 'array', 'date' => 'date', 'date_validation' => 'datetime', 'restaure_le' => 'datetime'];

    public function scopeSansPieceJustificative($query)
    {
        return $query->where(fn ($q) => $q->whereNull('piece_justificative')->orWhere('piece_justificative', ''))
            ->where(fn ($q) => $q->whereNull('pieces_justificatives')->orWhereJsonLength('pieces_justificatives', 0));
    }

    public function scopePerimetreTechnique($query)
    {
        $finances = Departement::all(['id', 'designation'])->filter(fn ($departement) =>
            \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(trim($departement->designation))) === 'direction financiere'
        )->pluck('id');

        return $query->where(function ($q) use ($finances) {
            $q->whereNotNull('departement_id')->whereNotIn('departement_id', $finances)
                ->orWhere(function ($legacy) {
                    $legacy->whereNull('departement_id')->where(function ($service) {
                        $service->whereNull('service')->orWhereRaw('LOWER(TRIM(service)) NOT IN (?, ?)', [
                            'direction financiere', 'direction financière',
                        ]);
                    });
                });
        });
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    /**
     * Relation avec les lignes de l'état de besoin
     */
    public function lignes()
    {
        return $this->hasMany(
            EtatBesoinLigne::class,
            'etat_besoin_id'
        );
    }

    public function sortieCaisses()
    {
        return $this->hasMany(SortieCaisse::class, 'etat_besoin_id');
    }
    public function ligneBudgetaire(){ return $this->belongsTo(LigneBudgetaire::class); }
    public function engagementBudgetaire(){ return $this->hasOne(EngagementBudgetaire::class); }
}
