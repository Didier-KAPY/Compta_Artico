<?php
namespace App\Models;
use Illuminate\Database\Eloquent\{Model,SoftDeletes};
class RhPaie extends Model
{
    use SoftDeletes;
    protected $table='rh_paies'; protected $guarded=[];
    protected $casts=['salaire_base'=>'decimal:2','primes'=>'decimal:2','retenues'=>'decimal:2','appliquer_retenues'=>'boolean','appliquer_retenue_absence'=>'boolean','retenue_absence_theorique'=>'decimal:2','heures_supplementaires'=>'decimal:2','montant_heures_supplementaires'=>'decimal:2','instantane_calcul'=>'array','total_gains'=>'decimal:2','salaire_brut'=>'decimal:2','salaire_imposable'=>'decimal:2','total_taxes'=>'decimal:2','total_cotisations'=>'decimal:2','salaire_net'=>'decimal:2','date_paiement'=>'date','valide_le'=>'datetime','bulletin_genere_le'=>'datetime','cloture_le'=>'datetime','modifie_le'=>'datetime'];
    protected static function booted():void { static::saving(function(self $p):void{$p->total_gains=$p->calculerTotalGains();$p->salaire_brut=$p->calculerTotalGains();$p->salaire_imposable=$p->salaire_imposable?:$p->calculerTotalGains();$p->salaire_net=$p->calculerNet();}); }
    public function employe(){return $this->belongsTo(Employe::class);} public function ancienUtilisateur(){return $this->belongsTo(User::class,'user_id');} public function lignes(){return $this->hasMany(RhLignePaie::class,'paie_id');} public function contrat(){return $this->belongsTo(RhContrat::class);} public function periodePaie(){return $this->belongsTo(RhPeriodePaie::class);} public function syntheseMensuelle(){return $this->belongsTo(RhSyntheseMensuelle::class);} public function paiements(){return $this->hasMany(RhPaiement::class,'paie_id');} public function historiques(){return $this->morphMany(RhWorkflowHistorique::class,'workflowable');}
    public function calculerTotalGains():float{return round((float)$this->salaire_base+(float)$this->primes+(float)$this->montant_heures_supplementaires,2);}
    public function calculerNet():float{return round($this->calculerTotalGains()-(float)$this->retenues-(float)$this->total_taxes-(float)$this->total_cotisations,2);}
    public function getNetAttribute():float{return $this->calculerNet();}
}
