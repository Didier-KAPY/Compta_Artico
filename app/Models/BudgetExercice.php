<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class BudgetExercice extends Model {
    use SoftDeletes;
    protected $fillable=['entreprise_id','exercice','periodicite','periode_numero','date_debut','date_fin','libelle','monnaie','montant_initial','statut','observations','created_by','valide_par','date_validation','cloture_par','date_cloture'];
    protected $casts=['date_debut'=>'date','date_fin'=>'date','montant_initial'=>'decimal:2','date_validation'=>'datetime','date_cloture'=>'datetime'];
    public function entreprise(){return $this->belongsTo(Entreprise::class);}
    public function lignes(){return $this->hasMany(LigneBudgetaire::class);}
    public function mouvements(){return $this->hasMany(MouvementBudgetaire::class);}
}
