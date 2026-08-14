<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RealisationBudgetaire extends Model {
    protected $table='realisations_budgetaires';
    protected $fillable=['engagement_budgetaire_id','sortie_caisse_id','montant_original','monnaie_originale','taux_change','montant_budgetaire','date_realisation','utilisateur_id','statut'];
    protected $casts=['montant_original'=>'decimal:2','taux_change'=>'decimal:6','montant_budgetaire'=>'decimal:2','date_realisation'=>'datetime'];
    public function engagement(){return $this->belongsTo(EngagementBudgetaire::class,'engagement_budgetaire_id');}
    public function sortieCaisse(){return $this->belongsTo(SortieCaisse::class);}
}
