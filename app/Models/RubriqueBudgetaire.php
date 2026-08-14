<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RubriqueBudgetaire extends Model
{
    use SoftDeletes;

    protected $table = 'rubriques_budgetaires';
    protected $fillable = ['code','designation','nature','liste_des_comptes_id','description','actif','created_by'];
    protected $casts = ['actif'=>'boolean'];

    public function compte() { return $this->belongsTo(ListeDesComptes::class, 'liste_des_comptes_id'); }
    public function lignes() { return $this->hasMany(LigneBudgetaire::class); }
    public function createur() { return $this->belongsTo(User::class, 'created_by'); }
}
