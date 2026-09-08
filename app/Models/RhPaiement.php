<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RhPaiement extends Model {
    protected $table='rh_paiements'; protected $guarded=[];
    protected $casts=['montant_net_a_payer'=>'decimal:2','montant_paye'=>'decimal:2','date_paiement'=>'date'];
    public function paie(){return $this->belongsTo(RhPaie::class);} public function employe(){return $this->belongsTo(Employe::class);}
}
