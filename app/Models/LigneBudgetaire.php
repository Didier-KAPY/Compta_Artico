<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LigneBudgetaire extends Model {
    use SoftDeletes;
    protected $table='lignes_budgetaires';
    protected $fillable=['budget_exercice_id','entreprise_id','departement_id','liste_des_comptes_id','rubrique_budgetaire_id','code','rubrique','description','date_debut','date_fin','prevision_initiale','revisions_positives','revisions_negatives','engagements_actifs','realisations','statut','created_by'];
    protected $casts=['date_debut'=>'date','date_fin'=>'date','prevision_initiale'=>'decimal:2','revisions_positives'=>'decimal:2','revisions_negatives'=>'decimal:2','engagements_actifs'=>'decimal:2','realisations'=>'decimal:2'];
    public function budget(){return $this->belongsTo(BudgetExercice::class,'budget_exercice_id');}
    public function entreprise(){return $this->belongsTo(Entreprise::class);}
    public function departement(){return $this->belongsTo(Departement::class);}
    public function compte(){return $this->belongsTo(ListeDesComptes::class,'liste_des_comptes_id');}
    public function rubriqueBudgetaire(){return $this->belongsTo(RubriqueBudgetaire::class);}
    public function engagements(){return $this->hasMany(EngagementBudgetaire::class);}
    public function mouvements(){return $this->hasMany(MouvementBudgetaire::class);}
    public function getBudgetReviseAttribute(): float{return (float)$this->prevision_initiale+(float)$this->revisions_positives-(float)$this->revisions_negatives;}
    public function getDisponibleAttribute(): float{return $this->budget_revise-(float)$this->engagements_actifs-(float)$this->realisations;}
    public function getEcartAttribute(): float{return $this->budget_revise-(float)$this->realisations;}
    public function getTauxExecutionAttribute(): float{return $this->budget_revise>0?((float)$this->realisations/$this->budget_revise)*100:0;}
    public function getTauxMobilisationAttribute(): float{return $this->budget_revise>0?(((float)$this->engagements_actifs+(float)$this->realisations)/$this->budget_revise)*100:0;}
}
