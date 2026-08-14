<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Departement;
use App\Models\EcritureComptable;
use App\Models\Entreprise;
use App\Models\ListeDesComptes;
use App\Models\BudgetExercice;
use App\Models\LigneBudgetaire;
use App\Models\EngagementBudgetaire;
use App\Models\RealisationBudgetaire;
use App\Models\MouvementBudgetaire;
use App\Models\MensualiteBudgetaire;
use App\Models\RubriqueBudgetaire;
use App\Services\BudgetService;
use App\Services\BudgetExecutionService;
use App\Services\AuditLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index()
    {
        return view('budgets.index', $this->pageData());
        /* Legacy dashboard calculation retained temporarily for migration comparison.
        $budgets = Budget::with(['departement', 'compte'])->latest('date_debut')->get()->map(function (Budget $budget) {
            $query = EcritureComptable::where('liste_des_comptes_id', $budget->liste_des_comptes_id)
                ->where('statut', 'Validé')->whereBetween('date', [$budget->date_debut, $budget->date_fin]);
            if ($budget->departement_id) {
                $query->whereHas('journal.user', fn ($q) => $q->where('departement_id', $budget->departement_id));
            }
            $budget->consomme = (float) $query->sum('debit_cdf');
            $budget->disponible = (float) $budget->montant_prevu - $budget->consomme;
            $budget->taux_consommation = (float) $budget->montant_prevu > 0
                ? ($budget->consomme / (float) $budget->montant_prevu) * 100
                : ($budget->consomme > 0 ? 100 : 0);
            return $budget;
        });
        $budgetSummary = [
            'initial' => (float) $budgets->sum(fn (Budget $budget) => (float) $budget->montant_prevu),
            'realise' => (float) $budgets->sum('consomme'),
            'disponible' => (float) $budgets->sum('disponible'),
            'nombre' => $budgets->count(),
            'alertes' => $budgets->filter(fn (Budget $budget) => $budget->taux_consommation >= 80)->count(),
        ];
        $budgetSummary['taux_execution'] = $budgetSummary['initial'] > 0
            ? ($budgetSummary['realise'] / $budgetSummary['initial']) * 100
            : 0;

        return view('budgets.index', [
            'budgets' => $budgets,
            'budgetSummary' => $budgetSummary,
            'departements' => Departement::orderBy('designation')->get(),
            'comptes' => ListeDesComptes::orderBy('compte')->get(),
        ]);
        */
    }

    public function budgetsAnnuels()
    {
        return view('budgets.annuels', $this->pageData());
    }

    public function lignesBudgetaires()
    {
        return view('budgets.lignes', $this->pageData());
    }

    public function rubriques()
    {
        return view('budgets.rubriques', $this->pageData());
    }

    public function storeRubrique(Request $request, AuditLogService $audit)
    {
        $data=$request->validate(['designation'=>['required','string','max:255'],'nature'=>['required','in:RECETTE,DEPENSE'],'liste_des_comptes_id'=>['required','exists:liste_des_comptes,id'],'description'=>['nullable','string']]);
        $compte=ListeDesComptes::findOrFail($data['liste_des_comptes_id']);
        if ($data['nature']==='RECETTE' && mb_strtolower((string)$compte->nature)!=='produit') throw \Illuminate\Validation\ValidationException::withMessages(['liste_des_comptes_id'=>'Une rubrique de recette doit utiliser un compte de nature Produit.']);
        if ($data['nature']==='DEPENSE' && mb_strtolower((string)$compte->nature)!=='charge') throw \Illuminate\Validation\ValidationException::withMessages(['liste_des_comptes_id'=>'Une rubrique de dépense doit utiliser un compte de nature Charge.']);
        $rubrique=RubriqueBudgetaire::create($data+['code'=>'RUB-'.\Illuminate\Support\Str::uuid(),'actif'=>true,'created_by'=>Auth::id()]);
        $audit->record('creation_rubrique_budgetaire',$rubrique,$rubrique->code,null,null,$rubrique->attributesToArray(),[],$request);
        return back()->with('success','Rubrique budgétaire créée.');
    }

    public function updateRubrique(Request $request, RubriqueBudgetaire $rubriqueBudgetaire, AuditLogService $audit)
    {
        $data=$request->validate(['designation'=>['required','string','max:255'],'description'=>['nullable','string'],'actif'=>['required','boolean']]);
        $avant=$rubriqueBudgetaire->attributesToArray(); $rubriqueBudgetaire->update($data);
        $audit->record('modification_rubrique_budgetaire',$rubriqueBudgetaire,$rubriqueBudgetaire->code,$request->input('motif'),$avant,$rubriqueBudgetaire->fresh()->attributesToArray(),[],$request);
        return back()->with('success','Rubrique mise à jour.');
    }

    public function engagements()
    {
        return view('budgets.engagements', $this->pageData());
    }

    public function recettes()
    {
        $data=$this->pageData(); $data['lignesBudgetaires']=$data['lignesBudgetaires']->where('nature_budgetaire','RECETTE')->values();
        return view('budgets.recettes',$data);
    }

    public function depenses()
    {
        $data=$this->pageData(); $data['lignesBudgetaires']=$data['lignesBudgetaires']->where('nature_budgetaire','DEPENSE')->values();
        return view('budgets.depenses',$data);
    }

    public function execution()
    {
        $data = $this->pageData();
        $data['lignesBudgetaires'] = $data['lignesBudgetaires']->where('statut', 'Active')->values();

        return view('budgets.execution', $data);
    }

    public function realisations(){ return view('budgets.realisations', $this->pageData()); }
    public function etats(){ return view('budgets.etats', $this->pageData()); }

    public function mouvements()
    {
        return view('budgets.mouvements', $this->pageData());
    }

    public function revisionsTransferts()
    {
        return view('budgets.revisions-transferts', $this->pageData());
    }

    public function edit(Budget $budget)
    {
        return view('budgets.edit', $this->pageData() + compact('budget'));
    }

    public function update(Request $request, Budget $budget)
    {
        $budget->update($this->validatedBudget($request));

        return redirect()->route('parametres.budgets.annuels')->with('success', 'Budget modifié avec succès.');
    }

    public function report(Request $request, string $rapport, string $format)
    {
        $data = $this->pageData();
        $budgets = $data['budgets'];
        [$titre, $headers, $rows] = match ($rapport) {
            'budgets' => ['Budgets annuels', ['Période', 'Département', 'Compte', 'Désignation', 'Prévision', 'Monnaie'], $budgets->map(fn ($b) => [
                $b->date_debut->format('d/m/Y').' - '.$b->date_fin->format('d/m/Y'), $b->departement?->designation ?? 'Tous',
                $b->compte?->compte ?? '—', $b->compte?->designation ?? '—', number_format((float) $b->montant_prevu, 2, ',', ' '), $b->monnaie,
            ])],
            'lignes' => ['Lignes budgétaires', ['Période', 'Département', 'Compte', 'Prévu CDF', 'Réalisé CDF', 'Disponible CDF', 'Exécution'], $budgets->map(fn ($b) => [
                $b->date_debut->format('d/m/Y').' - '.$b->date_fin->format('d/m/Y'), $b->departement?->designation ?? 'Tous',
                ($b->compte?->compte ?? '—').' - '.($b->compte?->designation ?? '—'), number_format((float) $b->montant_prevu, 2, ',', ' '),
                number_format($b->consomme, 2, ',', ' '), number_format($b->disponible, 2, ',', ' '), number_format($b->taux_consommation, 1, ',', ' ').' %',
            ])],
            'execution' => ["État d'exécution budgétaire", ['Compte', 'Prévision CDF', 'Réalisation CDF', 'Disponible CDF', "Taux d'exécution"], $budgets->map(fn ($b) => [
                ($b->compte?->compte ?? '—').' - '.($b->compte?->designation ?? '—'), number_format((float) $b->montant_prevu, 2, ',', ' '),
                number_format($b->consomme, 2, ',', ' '), number_format($b->disponible, 2, ',', ' '), number_format($b->taux_consommation, 1, ',', ' ').' %',
            ])],
        };
        if ($rapport === 'budgets' && $data['budgetExercices']->isNotEmpty()) {
            $titre='Budgets annuels'; $headers=['Exercice','Libellé','Entreprise','Monnaie','Montant initial','Statut'];
            $rows=$data['budgetExercices']->map(fn($b)=>[$b->exercice,$b->libelle,$b->entreprise?->nom_entreprise ?? '—',$b->monnaie,number_format((float)$b->montant_initial,2,',',' '),$b->statut]);
        }
        if (in_array($rapport,['lignes','execution'],true) && $data['lignesBudgetaires']->isNotEmpty()) {
            $lignesRapport = $rapport === 'execution'
                ? $data['lignesBudgetaires']->where('statut', 'Active')->values()
                : $data['lignesBudgetaires'];
            $titre=$rapport==='lignes'?'Lignes budgétaires':"État d'exécution budgétaire";
            $headers=['Rubrique','Nature','Compte','Prévision initiale','Révisions','Budget révisé','Engagement','Réalisation comptable','Disponible / Reste','Écart','Taux','Mobilisation'];
            $rows=$lignesRapport->map(fn($l)=>[$l->code.' - '.$l->rubrique,$l->nature_budgetaire,($l->compte?->compte ?? '—').' - '.($l->compte?->designation ?? '—'),number_format((float)$l->prevision_initiale,2,',',' '),number_format((float)$l->revisions_positives-(float)$l->revisions_negatives,2,',',' '),number_format($l->budget_revise,2,',',' '),number_format($l->nature_budgetaire==='DEPENSE'?(float)$l->engagements_actifs:0,2,',',' '),number_format($l->realise_comptable,2,',',' '),number_format($l->nature_budgetaire==='DEPENSE'?$l->disponible_comptable:$l->reste_a_realiser,2,',',' '),number_format($l->ecart_comptable,2,',',' '),number_format($l->taux_execution_comptable,1,',',' ').' %',number_format($l->taux_mobilisation_comptable,1,',',' ').' %']);
        }
        $dateDebut = $budgets->min('date_debut')?->toDateString() ?? now()->startOfYear()->toDateString();
        $dateFin = $budgets->max('date_fin')?->toDateString() ?? now()->endOfYear()->toDateString();
        $viewData = compact('titre', 'headers', 'rows', 'dateDebut', 'dateFin') + ['entreprise' => Entreprise::first()];
        $filename = $rapport.'-'.now()->format('Ymd-His');

        if ($format === 'print') {
            return view('budgets.report', $viewData);
        }
        if ($format === 'pdf') {
            return Pdf::loadView('exports.table', $viewData)->setPaper('a4', 'landscape')->download($filename.'.pdf');
        }

        $content = view('exports.table_excel', $viewData)->render();
        return response("\xEF\xBB\xBF".$content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.xls"',
        ]);
    }

    private function pageData(): array
    {
        $budgets = Budget::with(['departement', 'compte'])->latest('date_debut')->get()->map(function (Budget $budget) {
            $query = EcritureComptable::where('liste_des_comptes_id', $budget->liste_des_comptes_id)
                ->where('statut', 'Validé')->whereBetween('date', [$budget->date_debut, $budget->date_fin]);
            if ($budget->departement_id) {
                $query->whereHas('journal.user', fn ($q) => $q->where('departement_id', $budget->departement_id));
            }
            $budget->consomme = (float) $query->sum('debit_cdf');
            $budget->disponible = (float) $budget->montant_prevu - $budget->consomme;
            $budget->taux_consommation = (float) $budget->montant_prevu > 0
                ? ($budget->consomme / (float) $budget->montant_prevu) * 100
                : ($budget->consomme > 0 ? 100 : 0);
            return $budget;
        });
        $summary = [
            'initial' => (float) $budgets->sum(fn (Budget $budget) => (float) $budget->montant_prevu),
            'realise' => (float) $budgets->sum('consomme'),
            'disponible' => (float) $budgets->sum('disponible'),
            'nombre' => $budgets->count(),
            'alertes' => $budgets->filter(fn (Budget $budget) => $budget->taux_consommation >= 80)->count(),
        ];
        $summary['taux_execution'] = $summary['initial'] > 0 ? ($summary['realise'] / $summary['initial']) * 100 : 0;

        $budgetExercices = BudgetExercice::with('entreprise')->latest('exercice')->get();
        $lignesBudgetaires = LigneBudgetaire::with(['budget','departement','compte','rubriqueBudgetaire.compte'])->orderBy('code')->get();
        $execution = app(BudgetExecutionService::class);
        $lignesBudgetaires = $execution->enrichir($lignesBudgetaires);
        $syntheseBudgetaire = $execution->synthese($lignesBudgetaires);
        if ($lignesBudgetaires->isNotEmpty()) {
            $summary = [
                'initial'=>(float)$lignesBudgetaires->sum('prevision_initiale'),
                'revise'=>(float)$lignesBudgetaires->sum(fn($l)=>$l->budget_revise),
                'engage'=>(float)$lignesBudgetaires->sum('engagements_actifs'),
                'realise'=>(float)$lignesBudgetaires->sum('realisations'),
                'disponible'=>(float)$lignesBudgetaires->sum(fn($l)=>$l->disponible),
                'nombre'=>$lignesBudgetaires->count(),
                'alertes'=>$lignesBudgetaires->filter(fn($l)=>$l->taux_mobilisation>=80)->count(),
                'depassements'=>$lignesBudgetaires->filter(fn($l)=>$l->disponible<0)->count(),
            ];
            $summary['taux_execution']=$summary['revise']>0?($summary['realise']/$summary['revise'])*100:0;
            $summary['taux_mobilisation']=$summary['revise']>0?(($summary['engage']+$summary['realise'])/$summary['revise'])*100:0;
        }

        return [
            'budgets' => $budgets,
            'budgetSummary' => $summary,
            'departements' => Departement::orderBy('designation')->get(),
            'comptes' => ListeDesComptes::orderBy('compte')->get(),
            'rubriquesBudgetaires'=>RubriqueBudgetaire::with('compte')->where('actif',true)->orderBy('nature')->orderBy('designation')->get(),
            'syntheseBudgetaire'=>$syntheseBudgetaire,
            'budgetExercices'=>$budgetExercices,
            'lignesBudgetaires'=>$lignesBudgetaires,
            'engagementsBudgetaires'=>EngagementBudgetaire::with(['ligne','etatBesoin'])->latest('date_engagement')->get(),
            'realisationsBudgetaires'=>RealisationBudgetaire::with(['engagement.ligne','sortieCaisse'])->latest('date_realisation')->get(),
            'mouvementsBudgetaires'=>MouvementBudgetaire::with('ligne')->latest('date_mouvement')->get(),
        ];
    }

    public function storeExercice(Request $request, AuditLogService $audit)
    {
        $data=$request->validate(['periodicite'=>['required','in:annuel,semestriel,trimestriel']]);
        $entreprise=Entreprise::firstOrFail();
        $exercice=now()->year;
        $periodes=match($data['periodicite']) {
            'annuel'=>[[1,"{$exercice}-01-01","{$exercice}-12-31",'Budget annuel']],
            'semestriel'=>[[1,"{$exercice}-01-01","{$exercice}-06-30",'Premier semestre'],[2,"{$exercice}-07-01","{$exercice}-12-31",'Deuxième semestre']],
            'trimestriel'=>[[1,"{$exercice}-01-01","{$exercice}-03-31",'Premier trimestre'],[2,"{$exercice}-04-01","{$exercice}-06-30",'Deuxième trimestre'],[3,"{$exercice}-07-01","{$exercice}-09-30",'Troisième trimestre'],[4,"{$exercice}-10-01","{$exercice}-12-31",'Quatrième trimestre']],
        };
        $crees=0;
        foreach($periodes as [$numero,$debut,$fin,$libelle]) {
            $budget=BudgetExercice::firstOrCreate(['entreprise_id'=>$entreprise->id,'exercice'=>$exercice,'periodicite'=>$data['periodicite'],'periode_numero'=>$numero],['date_debut'=>$debut,'date_fin'=>$fin,'libelle'=>$libelle.' '.$exercice,'monnaie'=>$entreprise->monnaie_budgetaire ?: 'CDF','montant_initial'=>0,'statut'=>'Brouillon','created_by'=>Auth::id()]);
            if ($budget->wasRecentlyCreated) { $crees++; $audit->record('creation_budget',$budget,$budget->libelle,null,null,$budget->attributesToArray(),[],$request); }
        }
        return back()->with('success',$crees ? "{$crees} budget(s) créé(s) en brouillon." : 'Ces budgets existent déjà.');
    }

    public function statutExercice(Request $request, BudgetExercice $budgetExercice, BudgetService $service)
    {
        $data=$request->validate(['statut'=>['required','in:Validé,Clôturé'],'motif'=>['nullable','string','min:10']]);
        $service->changerStatut($budgetExercice,$data['statut'],$data['motif']??null);
        return back()->with('success','Statut du budget mis à jour.');
    }

    public function storeLigne(Request $request, AuditLogService $audit)
    {
        $data=$request->validate(['departement_id'=>['nullable','exists:departements,id'],'rubrique_budgetaire_id'=>['required','exists:rubriques_budgetaires,id'],'description'=>['nullable','string'],'date_debut'=>['required','date'],'date_fin'=>['required','date','after_or_equal:date_debut'],'prevision_initiale'=>['required','numeric','min:0']]);
        $rubrique=RubriqueBudgetaire::where('actif',true)->findOrFail($data['rubrique_budgetaire_id']);
        $data+=['liste_des_comptes_id'=>$rubrique->liste_des_comptes_id,'rubrique'=>$rubrique->designation];
        $entreprise=Entreprise::firstOrFail();
        $budget=BudgetExercice::where('entreprise_id',$entreprise->id)->where('exercice',(int)substr($data['date_debut'],0,4))->where('statut','!=','Clôturé')
            ->whereDate('date_debut','<=',$data['date_debut'])->whereDate('date_fin','>=',$data['date_fin'])->get()
            ->sortBy(fn (BudgetExercice $item) => $item->date_debut->diffInDays($item->date_fin))->first();
        if (! $budget) throw \Illuminate\Validation\ValidationException::withMessages(['budget'=>'Créez d’abord un budget annuel ouvert correspondant à l’année de la période.']);
        abort_if((int)substr($data['date_debut'],0,4)!==(int)$budget->exercice || (int)substr($data['date_fin'],0,4)!==(int)$budget->exercice,422,"La période doit appartenir à l’exercice budgétaire {$budget->exercice}.");
        $prochainNumero=(int)LigneBudgetaire::withTrashed()->where('budget_exercice_id',$budget->id)->count()+1;
        do { $code='LB-'.str_pad((string)$prochainNumero++,4,'0',STR_PAD_LEFT); }
        while (LigneBudgetaire::withTrashed()->where('budget_exercice_id',$budget->id)->where('code',$code)->exists());
        $ligne=LigneBudgetaire::create($data+['budget_exercice_id'=>$budget->id,'code'=>$code,'entreprise_id'=>$budget->entreprise_id,'statut'=>'En attente','created_by'=>Auth::id()]);
        $budget->update(['montant_initial'=>(float)$budget->lignes()->sum('prevision_initiale')]);
        $audit->record('creation_ligne_budgetaire',$ligne,$ligne->code,null,null,$ligne->attributesToArray(),[],$request);
        return back()->with('success','Ligne budgétaire créée.');
    }

    public function validerLigne(Request $request, LigneBudgetaire $ligneBudgetaire, AuditLogService $audit)
    {
        abort_if($ligneBudgetaire->budget->statut==='Clôturé',422,'Le budget annuel est clôturé.');
        $avant=$ligneBudgetaire->attributesToArray();
        $ligneBudgetaire->update(['statut'=>'Active']);
        $audit->record('validation_ligne_budgetaire',$ligneBudgetaire,$ligneBudgetaire->code,$request->input('motif'),$avant,$ligneBudgetaire->fresh()->attributesToArray(),[],$request);
        return back()->with('success','Ligne budgétaire validée.');
    }

    public function editLigne(LigneBudgetaire $ligneBudgetaire)
    {
        return view('budgets.edit-ligne', $this->pageData()+['ligne'=>$ligneBudgetaire->load(['budget','compte','departement'])]);
    }

    public function updateLigne(Request $request, LigneBudgetaire $ligneBudgetaire, AuditLogService $audit)
    {
        abort_if($ligneBudgetaire->budget->statut==='Clôturé',422,'Le budget annuel est clôturé.');
        $data=$request->validate(['departement_id'=>['nullable','exists:departements,id'],'rubrique_budgetaire_id'=>['required','exists:rubriques_budgetaires,id'],'description'=>['nullable','string'],'date_debut'=>['required','date'],'date_fin'=>['required','date','after_or_equal:date_debut'],'prevision_initiale'=>['required','numeric','min:0']]);
        $rubrique=RubriqueBudgetaire::where('actif',true)->findOrFail($data['rubrique_budgetaire_id']);
        $data+=['liste_des_comptes_id'=>$rubrique->liste_des_comptes_id,'rubrique'=>$rubrique->designation];
        abort_if((int)substr($data['date_debut'],0,4)!==(int)$ligneBudgetaire->budget->exercice || (int)substr($data['date_fin'],0,4)!==(int)$ligneBudgetaire->budget->exercice,422,"La période doit appartenir à l’exercice {$ligneBudgetaire->budget->exercice}.");
        abort_if((float)$data['prevision_initiale'] < (float)$ligneBudgetaire->engagements_actifs+(float)$ligneBudgetaire->realisations-(float)$ligneBudgetaire->revisions_positives+(float)$ligneBudgetaire->revisions_negatives,422,'La prévision modifiée rendrait le disponible négatif.');
        $avant=$ligneBudgetaire->attributesToArray();
        $ligneBudgetaire->update($data);
        $ligneBudgetaire->budget->update(['montant_initial'=>(float)$ligneBudgetaire->budget->lignes()->sum('prevision_initiale')]);
        $audit->record('modification_ligne_budgetaire',$ligneBudgetaire,$ligneBudgetaire->code,$request->input('motif'),$avant,$ligneBudgetaire->fresh()->attributesToArray(),[],$request);
        return redirect()->route('parametres.budgets.lignes')->with('success','Ligne budgétaire modifiée.');
    }

    public function destroyLigne(Request $request, LigneBudgetaire $ligneBudgetaire, AuditLogService $audit)
    {
        abort_if($ligneBudgetaire->budget->statut==='Clôturé',422,'Le budget annuel est clôturé.');
        if ($ligneBudgetaire->engagements()->exists() || $ligneBudgetaire->mouvements()->exists() || (float)$ligneBudgetaire->realisations>0) {
            throw \Illuminate\Validation\ValidationException::withMessages(['ligne'=>'Suppression impossible : cette ligne possède déjà un historique budgétaire.']);
        }
        $avant=$ligneBudgetaire->attributesToArray(); $budget=$ligneBudgetaire->budget;
        $ligneBudgetaire->delete();
        $budget->update(['montant_initial'=>(float)$budget->lignes()->sum('prevision_initiale')]);
        $audit->record('suppression_ligne_budgetaire',$ligneBudgetaire,$ligneBudgetaire->code,$request->input('motif','Suppression de la ligne budgétaire.'),$avant,$ligneBudgetaire->attributesToArray(),[],$request);
        return back()->with('success','Ligne budgétaire supprimée.');
    }

    public function revision(Request $request, LigneBudgetaire $ligneBudgetaire, BudgetService $service)
    {
        $data=$request->validate(['sens'=>['required','in:positive,negative'],'montant'=>['required','numeric','gt:0'],'motif'=>['required','string','min:10']]);
        $service->reviser($ligneBudgetaire,(float)$data['montant'],$data['sens'],$data['motif']);
        return back()->with('success','Révision budgétaire enregistrée.');
    }

    public function transfert(Request $request, BudgetService $service)
    {
        $data=$request->validate(['source_id'=>['required','exists:lignes_budgetaires,id','different:destination_id'],'destination_id'=>['required','exists:lignes_budgetaires,id'],'montant'=>['required','numeric','gt:0'],'motif'=>['required','string','min:10']]);
        $service->transferer(LigneBudgetaire::findOrFail($data['source_id']),LigneBudgetaire::findOrFail($data['destination_id']),(float)$data['montant'],$data['motif']);
        return back()->with('success','Transfert budgétaire effectué.');
    }

    public function mensualiser(Request $request, LigneBudgetaire $ligneBudgetaire)
    {
        $data=$request->validate(['mode'=>['required','in:egale,manuelle'],'montants'=>['nullable','array'],'montants.*'=>['nullable','numeric','min:0']]);
        abort_if($ligneBudgetaire->budget->statut==='Clôturé',422,'Budget clôturé.');
        if (! $ligneBudgetaire->date_debut || ! $ligneBudgetaire->date_fin) throw \Illuminate\Validation\ValidationException::withMessages(['periode'=>'Définissez la période de la ligne avant de la répartir.']);
        $debut=$ligneBudgetaire->date_debut->copy()->startOfMonth(); $fin=$ligneBudgetaire->date_fin->copy()->startOfMonth();
        $mois=[]; for($courant=$debut->copy();$courant->lte($fin);$courant->addMonth()) $mois[]=$courant->month;
        $nombreMois=count($mois);
        if ($data['mode']==='egale') {
            $montants=array_fill(0,$nombreMois,round($ligneBudgetaire->budget_revise/$nombreMois,2));
            $montants[$nombreMois-1]=round($ligneBudgetaire->budget_revise-array_sum(array_slice($montants,0,-1)),2);
        } else {
            $montants=array_map('floatval',$data['montants']??[]);
            if (count($montants)!==$nombreMois) throw \Illuminate\Validation\ValidationException::withMessages(['montants'=>"La saisie doit contenir {$nombreMois} montant(s), un par mois de la période."]);
        }
        abort_if(array_sum($montants)>$ligneBudgetaire->budget_revise+0.001,422,'La somme des mensualités dépasse le budget révisé.');
        DB::transaction(function() use($ligneBudgetaire,$montants,$mois,$data){
            MensualiteBudgetaire::where('ligne_budgetaire_id',$ligneBudgetaire->id)->whereNotIn('mois',$mois)->delete();
            foreach($mois as $index=>$moisNumero) MensualiteBudgetaire::updateOrCreate(['ligne_budgetaire_id'=>$ligneBudgetaire->id,'mois'=>$moisNumero],['montant'=>$montants[$index],'mode_repartition'=>$data['mode'],'created_by'=>Auth::id()]);
        });
        return back()->with('success',"Budget réparti sur les {$nombreMois} mois de la période.");
    }

    public function store(Request $request)
    {
        $data = $this->validatedBudget($request);
        Budget::updateOrCreate(collect($data)->except('montant_prevu')->all(), ['montant_prevu' => $data['montant_prevu'], 'cree_par' => Auth::id()]);
        return back()->with('success', 'Budget enregistré.');
    }

    private function validatedBudget(Request $request): array
    {
        return $request->validate([
            'departement_id' => ['nullable', 'exists:departements,id'],
            'liste_des_comptes_id' => ['required', 'exists:liste_des_comptes,id'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'montant_prevu' => ['required', 'numeric', 'min:0'],
            'monnaie' => ['required', 'in:CDF'],
        ]);
    }

    public function destroy(Budget $budget)
    {
        $budget->delete();
        return back()->with('success', 'Budget supprimé.');
    }
}
