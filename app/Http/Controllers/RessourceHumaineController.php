<?php

namespace App\Http\Controllers;

use App\Models\{Departement,Fonction,RhConge,RhContrat,RhEvaluation,RhPaie,RhPresence,User};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RessourceHumaineController extends Controller
{
    public function index() { return view('ressources_humaines.index', ['nombreEmployes'=>User::count(),'employesActifs'=>User::where('statut','Actif')->count(),'nombreDepartements'=>Departement::count(),'nombreFonctions'=>Fonction::count(),'congesAttente'=>RhConge::where('statut','En attente')->count(),'contratsExpiration'=>RhContrat::where('statut','Actif')->whereBetween('date_fin',[now(),now()->addDays(30)])->count()]); }
    public function employes() { return view('ressources_humaines.employes',['employes'=>User::with(['departement','fonction'])->orderBy('nom')->paginate(20)]); }
    public function contrats() { return $this->page('contrats',RhContrat::with('employe')->latest('date_debut')->get()); }
    public function presences() { return $this->page('presences',RhPresence::with('employe')->latest('date')->get()); }
    public function conges() { return $this->page('conges',RhConge::with(['employe','validateur'])->latest()->get()); }
    public function paie() { return $this->page('paie',RhPaie::with('employe')->orderByDesc('annee')->orderByDesc('mois')->get()); }
    public function evaluations() { return $this->page('evaluations',RhEvaluation::with(['employe','evaluateur'])->latest()->get()); }
    public function rapports() { return view('ressources_humaines.rapports',['effectif'=>User::with(['departement','fonction'])->orderBy('nom')->get(),'paies'=>RhPaie::with('employe')->get(),'conges'=>RhConge::with('employe')->get()]); }

    public function storeContrat(Request $r) { RhContrat::create($r->validate(['user_id'=>'required|exists:users,id','numero'=>'required|string|max:50|unique:rh_contrats','type'=>'required|in:CDI,CDD,Stage,Consultance','date_debut'=>'required|date','date_fin'=>'nullable|date|after_or_equal:date_debut','salaire_base'=>'required|numeric|min:0','statut'=>'required|in:Actif,Suspendu,Terminé','observations'=>'nullable|string'])); return back()->with('success','Contrat enregistré.'); }
    public function storePresence(Request $r) { RhPresence::updateOrCreate(['user_id'=>$r->validate(['user_id'=>'required|exists:users,id','date'=>'required|date'])['user_id'],'date'=>$r->date],$r->validate(['heure_arrivee'=>'nullable|date_format:H:i','heure_depart'=>'nullable|date_format:H:i|after:heure_arrivee','statut'=>'required|in:Présent,Absent,Retard,Mission','observation'=>'nullable|string'])); return back()->with('success','Présence enregistrée.'); }
    public function storeConge(Request $r) { RhConge::create($r->validate(['user_id'=>'required|exists:users,id','type'=>'required|in:Annuel,Maladie,Maternité,Paternité,Exceptionnel','date_debut'=>'required|date','date_fin'=>'required|date|after_or_equal:date_debut','motif'=>'nullable|string'])); return back()->with('success','Demande de congé enregistrée.'); }
    public function statutConge(Request $r,RhConge $conge) { $d=$r->validate(['statut'=>'required|in:Approuvé,Rejeté']); $conge->update($d+['valide_par'=>Auth::id(),'valide_le'=>now()]); return back()->with('success','Demande mise à jour.'); }
    public function storePaie(Request $r) { $d=$r->validate(['user_id'=>'required|exists:users,id','annee'=>'required|integer|min:2000|max:2100','mois'=>'required|integer|min:1|max:12','salaire_base'=>'required|numeric|min:0','primes'=>'nullable|numeric|min:0','retenues'=>'nullable|numeric|min:0','monnaie'=>'required|in:CDF,USD','statut'=>'required|in:Brouillon,Validée,Payée','date_paiement'=>'nullable|date']); RhPaie::updateOrCreate(['user_id'=>$d['user_id'],'annee'=>$d['annee'],'mois'=>$d['mois']],$d); return back()->with('success','Paie enregistrée.'); }
    public function storeEvaluation(Request $r) { RhEvaluation::create($r->validate(['user_id'=>'required|exists:users,id','periode'=>'required|string|max:50','note'=>'required|integer|min:0|max:100','objectifs'=>'nullable|string','commentaire'=>'nullable|string'])+['evalue_par'=>Auth::id()]); return back()->with('success','Évaluation enregistrée.'); }
    public function destroy(string $type,int $id) { $model=match($type){'contrats'=>RhContrat::class,'presences'=>RhPresence::class,'conges'=>RhConge::class,'paie'=>RhPaie::class,'evaluations'=>RhEvaluation::class,default=>abort(404)}; $model::findOrFail($id)->delete(); return back()->with('success','Enregistrement supprimé.'); }
    public function report(string $format) { $data=['titre'=>'Rapport des ressources humaines','headers'=>['Employé','Département','Fonction','Statut'],'rows'=>User::with(['departement','fonction'])->orderBy('nom')->get()->map(fn($u)=>[$u->nom.' '.$u->prenom,$u->departement?->designation??'—',$u->fonction?->designation??'—',$u->statut]),'dateDebut'=>now()->startOfYear()->toDateString(),'dateFin'=>now()->toDateString(),'entreprise'=>\App\Models\Entreprise::first()]; if($format==='print') return view('budgets.report',$data); if($format==='pdf') return Pdf::loadView('exports.table',$data)->setPaper('a4','landscape')->download('rapport-rh.pdf'); $content=view('exports.table_excel',$data)->render(); return response("\xEF\xBB\xBF".$content,200,['Content-Type'=>'application/vnd.ms-excel; charset=UTF-8','Content-Disposition'=>'attachment; filename="rapport-rh.xls"']); }
    private function page(string $module,$elements) { return view('ressources_humaines.gestion',compact('module','elements')+['employes'=>User::where('statut','Actif')->orderBy('nom')->get()]); }
}
