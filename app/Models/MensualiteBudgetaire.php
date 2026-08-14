<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MensualiteBudgetaire extends Model {
    protected $table='mensualites_budgetaires';
    protected $fillable=['ligne_budgetaire_id','mois','montant','mode_repartition','created_by'];
    protected $casts=['montant'=>'decimal:2'];
    public function ligne(){return $this->belongsTo(LigneBudgetaire::class,'ligne_budgetaire_id');}
}
