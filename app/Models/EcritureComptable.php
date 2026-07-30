<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\ListeDesComptes;
use App\Models\Journaux;
use App\Models\User;


class EcritureComptable extends Model
{

    use HasFactory;


    protected $table = 'ecritures_comptables';



    protected $fillable = [

        'user_id',

        'journal_id',

        'liste_des_comptes_id',

        'date',

        'piece',

        'libelle',

        'debit_cdf',

        'credit_cdf',
        'statut',
        'valide_par',
        'date_validation',

    ];





    protected $casts = [

        'date' => 'date',

        'debit_cdf' => 'decimal:2',

        'credit_cdf' => 'decimal:2',
        'date_validation' => 'datetime',
    ];







    /*
    |--------------------------------------------------------------------------
    | Utilisateur créateur de l'écriture
    |--------------------------------------------------------------------------
    */

    public function user()
    {

        return $this->belongsTo(
            User::class,
            'user_id'
        );

    }







    /*
    |--------------------------------------------------------------------------
    | Journal associé
    |--------------------------------------------------------------------------
    */

    public function journal()
    {

        return $this->belongsTo(
            Journaux::class,
            'journal_id'
        );

    }







    /*
    |--------------------------------------------------------------------------
    | Compte comptable
    |--------------------------------------------------------------------------
    */

    public function compte()
    {

        return $this->belongsTo(
            ListeDesComptes::class,
            'liste_des_comptes_id'
        );

    }


}
