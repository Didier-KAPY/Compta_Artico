<?php
namespace App\Http\Controllers;
use App\Models\{RhPaie,RhPaiement};
use App\Services\{CurrentEntreprise,RhPaymentReferenceService,RhWorkflowService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RhPaiementController extends Controller {
    public function store(Request $r,RhPaie $paie,RhPaymentReferenceService $refs,RhWorkflowService $workflow){$this->sameCompany($r,$paie);if($paie->statut!=='Prête au paiement')throw ValidationException::withMessages(['paie'=>'La paie doit être prête au paiement.']);$d=$r->validate(['montant_paye'=>'required|numeric|min:0.01','mode_paiement'=>'required|in:Espèces,Mobile Money,Virement bancaire,Chèque','date_paiement'=>'required|date','reference_paiement'=>'nullable|string|max:190','compte_paiement'=>'nullable|string|max:190','observation'=>'nullable|string']);$reste=round((float)$paie->salaire_net-(float)$paie->paiements()->where('statut','!=','Annulé')->sum('montant_paye'),2);if((float)$d['montant_paye']>$reste)throw ValidationException::withMessages(['montant_paye'=>'Le montant dépasse le solde restant.']);$paiement=DB::transaction(function()use($r,$paie,$refs,$workflow,$d,$reste){$p=RhPaiement::create($d+['entreprise_id'=>$paie->entreprise_id,'paie_id'=>$paie->id,'employe_id'=>$paie->employe_id,'montant_net_a_payer'=>$paie->salaire_net,'devise'=>$paie->monnaie,'reference_paiement'=>$d['reference_paiement']??$refs->generate($paie->entreprise_id,$paie->annee,$paie->mois),'paye_par'=>$r->user()->id,'statut'=>(float)$d['montant_paye']<$reste?'Partiellement payé':'Payé']);$avant=$paie->toArray();if((float)$d['montant_paye']>=$reste)$paie->update(['statut'=>'Payée','date_paiement'=>$d['date_paiement'],'mode_paiement'=>$d['mode_paiement'],'reference_paiement'=>$p->reference_paiement,'paye_par'=>$r->user()->id]);$workflow->trace($paie,'paiement',$r->user()->id,null,$avant,$paie->fresh()->toArray());return $p;});return back()->with('success','Paiement '.$paiement->statut.' enregistré.');}
    private function sameCompany(Request $r,$m):void{abort_unless((int)$m->entreprise_id===(int)app(CurrentEntreprise::class)->for($r->user())->id,404);}
}
