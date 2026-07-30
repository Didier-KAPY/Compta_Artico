<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\User;
use App\Models\JournalType;
use App\Models\EntreeCaisse;
use App\Models\SortieCaisse;
use App\Models\EcritureComptable;
use App\Models\ListeDesComptes;


class Journaux extends Model
{

    use HasFactory;


    protected $table = 'journaux';



    protected $fillable = [

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

    ];





    protected $casts = [

        'date' => 'date',

        'date_validation' => 'datetime',

        'montant_ht' => 'decimal:2',

        'taux_tva' => 'decimal:2',

        'montant_tva' => 'decimal:2',

        'montant_ttc' => 'decimal:2',

        'entrees_cdf' => 'decimal:2',

        'sorties_cdf' => 'decimal:2',

        'entrees_usd' => 'decimal:2',

        'sorties_usd' => 'decimal:2',

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
