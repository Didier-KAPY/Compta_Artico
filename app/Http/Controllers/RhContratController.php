<?php
namespace App\Http\Controllers;
use App\Http\Requests\StoreRhContratRequest;
use App\Models\{Employe,RhContrat};
use App\Services\{AuditLogService,CurrentEntreprise,RhContratService};
use Illuminate\Http\Request;
class RhContratController extends Controller {public function index(CurrentEntreprise $ctx){$eid=$ctx->for()->id;return view('ressources_humaines.gestion',['module'=>'contrats','elements'=>RhContrat::with('employe')->where('entreprise_id',$eid)->latest('date_debut')->paginate(20),'employes'=>Employe::where('entreprise_id',$eid)->where('statut','Actif')->orderBy('nom')->get()]);}public function store(StoreRhContratRequest $r,RhContratService $service){$m=$service->creer($r->validated(),$r->user()->id);app(AuditLogService::class)->record('creation_rh',$m,$m->numero,null,null,$m->toArray(),[],$r);return back()->with('success','Contrat enregistré.');}public function validateContract(Request $r,RhContrat $contrat,RhContratService $service){$r->user()->can('validateContracts')||abort(403);$old=$contrat->toArray();$service->valider($contrat,$r->user()->id);app(AuditLogService::class)->record('validation_rh',$contrat,$contrat->numero,null,$old,$contrat->fresh()->toArray(),[],$r);return back()->with('success','Contrat validé.');}}
