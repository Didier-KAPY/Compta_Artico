<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntreeCaisseLigne extends Model
{
    protected $table = 'entree_caisse_lignes';

    protected $fillable = [
        'entree_caisse_id',
        'designation',
        'quantite',
        'prix_unitaire',
        'montant'
    ];

    /**
     * Relation : une ligne appartient à une entrée de caisse
     */
    public function entreeCaisse()
    {
        return $this->belongsTo(EntreeCaisse::class);
    }
}