<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\ListeDesComptes;
use App\Models\Journaux;
use App\Models\User;


class EcritureComptable extends Model
{

    use HasFactory, SoftDeletes;


    protected $table = 'ecritures_comptables';



    protected $fillable = [

        'user_id',

        'journal_id',

        'liste_des_comptes_id',

        'date',

        'piece',

        'piece_justificative',

        'libelle',

        'debit_cdf',

        'credit_cdf',
        'statut',
        'valide_par',
        'date_validation',
        'motif_suppression', 'supprime_par', 'restaure_par', 'restaure_le',

    ];





    protected $casts = [

        'date' => 'date',

        'debit_cdf' => 'decimal:2',

        'credit_cdf' => 'decimal:2',
        'date_validation' => 'datetime',
        'restaure_le' => 'datetime',
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

    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
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
