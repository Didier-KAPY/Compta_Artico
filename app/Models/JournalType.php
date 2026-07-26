<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ListeDesComptes;
use App\Models\User;

class JournalType extends Model
{
    use HasFactory;
    protected $table = 'journal_types';

protected $fillable=[
'user_id',
'code',
'libelle',
'liste_des_comptes_id',
'nature',
'est_tresorerie'
];


public function compte()
{
return $this->belongsTo(
ListeDesComptes::class,
'liste_des_comptes_id'
);
}


public function journaux()
{
return $this->hasMany(
Journaux::class,
'journal_type_id'
);
}
public function user()
{
    return $this->belongsTo(
        User::class
    );
}

}