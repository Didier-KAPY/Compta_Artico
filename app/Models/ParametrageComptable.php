<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ParametrageComptable extends Model
{

    use HasFactory;


    protected $table = 'parametres_comptables';



    protected $fillable = [

        'user_id',

        'code',

        'designation',

        'liste_des_comptes_id',

    ];



    public function compte()
    {

        return $this->belongsTo(
            ListeDesComptes::class,
            'liste_des_comptes_id'
        );

    }


}