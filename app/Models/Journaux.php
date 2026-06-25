<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Journaux  extends Model
{
    use HasFactory;

    protected $table = 'journaux';

    protected $fillable = [
        'user_id',
        'liste_des_comptes_id',
        'entree_caisse_id',
        'sortie_caisse_id',
        'input',
        'noms_client',
        'téléphone',
        'journal_type_id',
        'taux_de_change_id',
        'date',
        'reference',
        'description',
        'piece_justificatif',
        'mode_paiement',
        'monnaie',
        'type',
        'entrees_cdf',
        'sorties_cdf',
        'entrees_usd',
        'sorties_usd',
        'total_entrees_cdf',
        'total_sorties_cdf',
        'statut',
        'date_validation',
        'valide_par',
    ];

    /*
    |-------------------------
    | RELATIONS
    |-------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function journalType()
    {
        return $this->belongsTo(JournalType::class, 'journal_type_id');
    }

    public function compte()
    {
        return $this->belongsTo(ListeDesComptes::class, 'liste_des_comptes_id');
    }

    public function tauxDeChange()
    {
        return $this->belongsTo(TauxDeChange::class, 'taux_de_change_id');
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
        return $this->belongsTo(
            SortieCaisse::class,
            'sortie_caisse_id'
        );
    }
}
