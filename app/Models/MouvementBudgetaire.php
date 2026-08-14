<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MouvementBudgetaire extends Model {
    protected $table='mouvements_budgetaires';
    protected $fillable=['budget_exercice_id','ligne_budgetaire_id','engagement_budgetaire_id','realisation_budgetaire_id','operation_uuid','type','montant','monnaie','source_type','source_id','reference_document','ancienne_situation','nouvelle_situation','utilisateur_id','date_mouvement','motif'];
    protected $casts=['montant'=>'decimal:2','ancienne_situation'=>'array','nouvelle_situation'=>'array','date_mouvement'=>'datetime'];
    public function source(){return $this->morphTo();}
    public function ligne(){return $this->belongsTo(LigneBudgetaire::class,'ligne_budgetaire_id');}
}
