<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\User;
use App\Models\JournalType;
use App\Models\EntreeCaisse;
use App\Models\SortieCaisse;
use App\Models\EcritureComptable;
use App\Models\ListeDesComptes;


class Journaux extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $journal) {
            if (!$journal->entreprise_id && auth()->user() && Entreprise::exists()) $journal->entreprise_id = app(\App\Services\CurrentEntreprise::class)->for()->id;
        });
        static::updating(function (self $journal) {
            if ($journal->constatation()->exists() && $journal->isDirty(['date','reference','liste_des_comptes_id','journal_type_id','entree_caisse_id','sortie_caisse_id','monnaie','montant_ttc','entrees_cdf','sorties_cdf','entrees_usd','sorties_usd','statut'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['constatation'=>'Le règlement lié à une constatation validée est verrouillé.']);
            }
        });
        static::deleting(function (self $journal) {
            if ($journal->constatation()->exists()) throw \Illuminate\Validation\ValidationException::withMessages(['constatation'=>'Le règlement lié à une constatation validée ne peut pas être supprimé.']);
        });
    }
    public function constatation() { return $this->hasOne(ConstatationComptable::class, 'reglement_journal_id'); }

    use HasFactory, SoftDeletes;


    protected $table = 'journaux';



    protected $fillable = [
        'entreprise_id',

        'user_id',

        'journal_type_id',

        'liste_des_comptes_id',
        'entree_caisse_id',
        'sortie_caisse_id',

        'reference',

        'date',

        'nom_partenaire',

        'telephone_partenaire',

        'adresse_partenaire',

        'description',

        'observation',

        'piece_justificatif',

        'type',

        'monnaie',

        'mode_paiement',

        'montant_ht',

        'taux_tva',

        'montant_tva',

        'montant_ttc',

        'entrees_cdf',

        'sorties_cdf',

        'entrees_usd',

        'sorties_usd',

        'statut',

        'date_validation',

        'valide_par',
        'statut_regroupement', 'cloture_journaliere_id', 'regroupe_le',
        'motif_suppression', 'supprime_par', 'restaure_par', 'restaure_le',

    ];





    protected $casts = [

        'date' => 'date',

        'date_validation' => 'datetime',

        'montant_ht' => 'decimal:18',

        'taux_tva' => 'decimal:2',

        'montant_tva' => 'decimal:18',

        'montant_ttc' => 'decimal:18',

        'entrees_cdf' => 'decimal:2',

        'sorties_cdf' => 'decimal:18',

        'entrees_usd' => 'decimal:2',

        'sorties_usd' => 'decimal:18',
        'restaure_le' => 'datetime',
        'regroupe_le' => 'datetime',

    ];





    /*
    |--------------------------------------------------------------------------
    | Utilisateur créateur
    |--------------------------------------------------------------------------
    */

    public function user()
    {

        return $this->belongsTo(
            User::class
        );

    }





    /*
    |--------------------------------------------------------------------------
    | Utilisateur validateur
    |--------------------------------------------------------------------------
    */

    public function validateur()
    {

        return $this->belongsTo(
            User::class,
            'valide_par'
        );

    }






    /*
    |--------------------------------------------------------------------------
    | Type de journal
    |--------------------------------------------------------------------------
    */

    public function journalType()
    {

        return $this->belongsTo(
            JournalType::class,
            'journal_type_id'
        );

    }
    public function entreeCaisse()
{
    return $this->belongsTo(
        EntreeCaisse::class,
        'entree_caisse_id'
    );
}

    public function sortieCaisse()
    {
        return $this->belongsTo(SortieCaisse::class, 'sortie_caisse_id');
    }





    /*
    |--------------------------------------------------------------------------
    | Compte lié au journal
    |--------------------------------------------------------------------------
    */

    public function getLibelleReleveAttribute(): string
    {
        if (mb_strtolower(trim((string) $this->description)) !== 'tva') {
            return (string) $this->description;
        }

        if ($this->entree_caisse_id) {
            return 'TVA_FACTUREE';
        }

        if ($this->sortie_caisse_id) {
            return 'TVA_RECUPERABLE';
        }

        return 'TVA';
    }
    public function compte()
    {

        return $this->belongsTo(
            ListeDesComptes::class,
            'liste_des_comptes_id'
        );

    }








    /*
    |--------------------------------------------------------------------------
    | Ecritures comptables
    |--------------------------------------------------------------------------
    */

    public function ecritures()
    {

        return $this->hasMany(
            EcritureComptable::class,
            'journal_id'
        );

    }

    public function brcs()
    {
        return $this->belongsToMany(BRC::class, 'brc_journal', 'journal_id', 'brc_id');
    }

    public function clotureJournaliere()
    {
        return $this->belongsTo(ClotureJournaliere::class);
    }

    public function rattachementCloture()
    {
        return $this->hasOne(ClotureJournaliereJournal::class, 'journal_id');
    }









    /*
    |--------------------------------------------------------------------------
    | Total entrées
    |--------------------------------------------------------------------------
    */

    public function getTotalEntreesAttribute()
    {

        return
            $this->entrees_cdf +
            $this->entrees_usd;

    }







    /*
    |--------------------------------------------------------------------------
    | Total sorties
    |--------------------------------------------------------------------------
    */

    public function getTotalSortiesAttribute()
    {

        return
            $this->sorties_cdf +
            $this->sorties_usd;

    }







    /*
    |--------------------------------------------------------------------------
    | Etat journal
    |--------------------------------------------------------------------------
    */

    public function estValide()
    {

        return $this->statut === 'Validé';

    }





    public function estRejete()
    {

        return $this->statut === 'Rejeté';

    }






    /*
    |--------------------------------------------------------------------------
    | Libellé affichage
    |--------------------------------------------------------------------------
    */

    public function getLibelleOperationAttribute()
    {

        return strtoupper($this->type)
            ." - "
            .$this->description;

    }


}
