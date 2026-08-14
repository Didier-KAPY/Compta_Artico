<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EngagementBudgetaire extends Model {
    protected $table='engagements_budgetaires';
    protected $fillable=['budget_exercice_id','ligne_budgetaire_id','etat_besoin_id','entreprise_id','montant_original','monnaie_originale','taux_change','date_taux','montant_budgetaire','montant_restant','montant_realise','statut','date_engagement','utilisateur_id','motif'];
    protected $casts=['montant_original'=>'decimal:2','taux_change'=>'decimal:6','date_taux'=>'date','montant_budgetaire'=>'decimal:2','montant_restant'=>'decimal:2','montant_realise'=>'decimal:2','date_engagement'=>'datetime'];
    public function budget(){return $this->belongsTo(BudgetExercice::class,'budget_exercice_id');}
    public function ligne(){return $this->belongsTo(LigneBudgetaire::class,'ligne_budgetaire_id');}
    public function etatBesoin(){return $this->belongsTo(EtatBesoin::class);}
    public function realisations(){return $this->hasMany(RealisationBudgetaire::class);}
}
