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
    protected static function booted(): void
    {
        static::creating(function (self $line) {
            if (!$line->entreprise_id && $line->journal_id) $line->entreprise_id = Journaux::whereKey($line->journal_id)->value('entreprise_id');
            if (!$line->entreprise_id && auth()->user() && Entreprise::exists()) $line->entreprise_id = app(\App\Services\CurrentEntreprise::class)->for()->id;
        });
        static::updating(function (self $line) {
            if ($line->getOriginal('constatation_id') && $line->isDirty(['entreprise_id', 'journal_id', 'liste_des_comptes_id', 'date', 'piece', 'libelle', 'debit_cdf', 'credit_cdf', 'statut', 'constatation_id', 'role_constatation', 'nature_constatation'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['constatation'=>'Une écriture liée à une constatation validée est verrouillée.']);
            }
        });
        static::deleting(function (self $line) {
            if ($line->constatation_id) throw \Illuminate\Validation\ValidationException::withMessages(['constatation'=>'Une écriture liée à une constatation validée ne peut pas être supprimée.']);
        });
    }

    public function constatation() { return $this->belongsTo(ConstatationComptable::class, 'constatation_id'); }

    use HasFactory, SoftDeletes;


    protected $table = 'ecritures_comptables';



    protected $fillable = [
        'entreprise_id', 'constatation_id', 'role_constatation', 'nature_constatation',

        'user_id',

        'journal_id',

        'liste_des_comptes_id',

        'date',

        'piece',

        'piece_justificative',
        'pieces_justificatives',

        'libelle',

        'debit_cdf',

        'credit_cdf',
        'statut',
        'valide_par',
        'date_validation',
        'motif_suppression', 'supprime_par', 'restaure_par', 'restaure_le',

    ];





    protected $casts = ['pieces_justificatives' => 'array', 

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
