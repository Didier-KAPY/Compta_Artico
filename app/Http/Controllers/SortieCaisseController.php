<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteFinancialDocumentRequest;
use App\Services\FinancialDocumentService;

use Illuminate\Http\Request;
use App\Models\SortieCaisse;
use App\Models\SortieCaisseLigne;
use App\Models\JournalType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Services\WorkflowComptableService;
use App\Services\BudgetService;
use App\Models\Journaux;
use App\Models\ParametrageComptable;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Entreprise;
use Illuminate\Support\Facades\Storage;
use App\Services\DocumentNumberService;

class SortieCaisseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
 public function index(Request $request)
{
    $query = $this->sortiesFiltrees($request);

    $sorties = $query
        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view('sortie_caisses.index', compact('sorties'));
}

    private function sortiesFiltrees(Request $request)
    {
        return SortieCaisse::with(['etatBesoin', 'user', 'validateur'])
            ->when($request->filled('numero'), fn ($query) => $query->where('numero', 'like', '%'.$request->numero.'%'))
            ->when($request->filled('date_debut'), fn ($query) => $query->whereDate('date', '>=', $request->date_debut))
            ->when($request->filled('date_fin'), fn ($query) => $query->whereDate('date', '<=', $request->date_fin));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('sortie_caisses.create', [
            'tauxTva' => (float) config('syscohada.taux_tva', 16),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, DocumentNumberService $numbers)
    {
        $decimal = static fn ($value) => is_string($value)
            ? str_replace(',', '.', preg_replace('/[\s\x{00A0}]+/u', '', trim($value)))
            : $value;
        $request->merge([
            'quantite' => collect($request->input('quantite', []))->map($decimal)->all(),
            'prix_unitaire' => collect($request->input('prix_unitaire', []))->map($decimal)->all(),
        ]);
        $data = $request->validate([
            'date' => 'required|date', 'beneficiaire' => 'required|string|max:255',
            'monnaie' => 'required|in:CDF,USD', 'type_bon' => 'required|in:BSC,BSB,BSM',
            'appliquer_tva' => 'nullable|boolean', 'observation' => 'nullable|string',
            'designation' => 'required|array|min:1', 'designation.*' => 'required|string|max:255',
            'quantite' => 'required|array|min:1', 'quantite.*' => 'required|numeric|min:0.01',
            'prix_unitaire' => 'required|array|min:1', 'prix_unitaire.*' => 'required|numeric|min:0',
        ]);
        $type = match ($data['type_bon']) { 'BSB' => 'Banque', 'BSM' => 'Mobile Money', default => 'Caisse' };
        $appliquerTva = $request->boolean('appliquer_tva');
        $tauxTva = $appliquerTva ? round((float) config('syscohada.taux_tva', 16), 2) : 0;
        $motif = trim((string) $data['designation'][0]);

        DB::transaction(function () use ($data, $numbers, $type, $appliquerTva, $tauxTva, $motif) {
            $total = collect(array_keys($data['designation']))->sum(
                fn ($index) => (float) $data['quantite'][$index] * (float) $data['prix_unitaire'][$index]
            );
            $montantHt = $appliquerTva && $tauxTva > 0 ? round($total / (1 + $tauxTva / 100), 2) : round($total, 2);
            $montantTva = $appliquerTva ? round($total - $montantHt, 2) : 0;
            $sortie = SortieCaisse::create([
                'numero' => $numbers->next('BSC', $data['date'], $type), 'type_bon' => $data['type_bon'],
                'date' => $data['date'], 'beneficiaire' => $data['beneficiaire'], 'motif' => $motif,
                'montant' => $total, 'appliquer_tva' => $appliquerTva, 'taux_tva' => $tauxTva,
                'montant_ht' => $montantHt, 'montant_tva' => $montantTva, 'monnaie' => $data['monnaie'],
                'type' => $type, 'observation' => $data['observation'] ?? null,
                'statut' => 'En attente', 'user_id' => auth()->id(),
            ]);
            foreach ($data['designation'] as $index => $designation) {
                $quantite = (float) $data['quantite'][$index];
                $prix = (float) $data['prix_unitaire'][$index];
                SortieCaisseLigne::create(['sortie_caisse_id' => $sortie->id, 'designation' => $designation,
                    'quantite' => $quantite, 'prix_unitaire' => $prix, 'montant' => $quantite * $prix]);
            }
            if ($appliquerTva) {
                $sortie->lignesCloture()->delete();
                SortieCaisseLigne::create(['sortie_caisse_id'=>$sortie->id,'designation'=>$motif.' (HT)','quantite'=>1,'prix_unitaire'=>$montantHt,'montant'=>$montantHt]);
                SortieCaisseLigne::create(['sortie_caisse_id'=>$sortie->id,'designation'=>'TVA '.number_format($tauxTva, 0).' %','quantite'=>1,'prix_unitaire'=>$montantTva,'montant'=>$montantTva]);
            }
        });
        return redirect()->route('sortie-caisses.create')->with('success', 'Bon de sortie créé avec succès.');
    }
    /**
     * Display the specified resource.
     */
 public function show($id, FinancialDocumentService $documents)
{
    $sortie = SortieCaisse::with([
        'user',
        'etatBesoin.lignes',
        'journaux.ecritures',
        'clotureJournaliere',
        'lignesCloture.journal',
    ])->findOrFail($id);
    $suppressionDependencies = $documents->dependencies($sortie);
    $documentLinks = collect([
        $sortie->etatBesoin ? ['label' => "Voir l’État de besoin", 'url' => route('etat-besoins.show', $sortie->etatBesoin), 'icon' => 'file-earmark-text'] : null,
    ])->filter()->values();



    $roleObservation = strtolower(
        auth()->user()
        ->role
        ?->observation ?? ''
    );



    $journalTypes = collect();



   $journalTypes = collect();


    if(
        str_contains($roleObservation,'caisse') ||
        str_contains($roleObservation,'banque') ||
        str_contains($roleObservation,'monnaie electronique') ||
        str_contains($roleObservation,'mobile money')
    ) {


        $journalTypes = JournalType::where('est_tresorerie', true)
            ->get();


    } else {


        $journalTypes = JournalType::where('est_tresorerie', true)
            ->get();

    }

    $journalCaisseValide = $this->journauxDuBon($sortie)
        ->get()
        ->contains(fn ($journal) => $this->statutEstValide($journal->statut));

    
    return view(
        'sortie_caisses.show',
        compact(
            'sortie',
            'journalTypes',
            'journalCaisseValide',
            'suppressionDependencies',
            'documentLinks',
        )
    );
}

public function imprimer($id)
{
    return view('sortie_caisses.document', $this->donneesDocument($id) + ['pdfMode' => false]);
}

public function telechargerPdf($id)
{
    $data = $this->donneesDocument($id) + ['pdfMode' => true];
    $nom = preg_replace('/[^A-Za-z0-9_-]/', '-', $data['sortie']->numero);

    return Pdf::loadView('sortie_caisses.document', $data)
        ->setPaper('a4', 'portrait')
        ->download('bon-de-sortie-'.$nom.'.pdf');
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $sortie = SortieCaisse::findOrFail($id);

        if ($sortie->statut === 'Validé') {
            return back()->with('error', 'Ce Bon validé doit d’abord être réouvert.');
        }

        return view('sortie_caisses.edit', compact('sortie'));
    }

    /**
     * Update the specified resource in storage.
     */
 public function update(Request $request, string $id, DocumentNumberService $numbers)
{
    $request->validate([
        'date' => 'required|date',
        'beneficiaire' => 'required|string|max:255',
        'motif' => 'required|string',
        'montant' => 'required|numeric|min:0',
        'monnaie' => 'required|in:CDF,USD',
        'type' => 'required|in:Caisse,Banque,Mobile Money',
        'observation' => 'required|string',
    ]);

    $sortie = SortieCaisse::findOrFail($id);

    if ($sortie->statut === 'Validé') {
        return back()->with('error', 'Ce Bon validé doit d’abord être réouvert.');
    }

    // Générer un numéro seulement si absent
    if (empty($sortie->numero)) {
        $sortie->numero = $numbers->next('BSC', $request->date, $request->type);
    }

    $sortie->date = $request->date;
    $sortie->beneficiaire = $request->beneficiaire;
    $sortie->motif = $request->motif;
    $sortie->montant = $request->montant;
    $sortie->monnaie = $request->monnaie;
    $sortie->type = $request->type;
    $sortie->observation = $request->observation;

    $sortie->save();

    return redirect()
        ->route('sortie-caisses.show', $sortie->id)
        ->with('success', 'Bon de sortie modifié avec succès.');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteFinancialDocumentRequest $request, string $id, FinancialDocumentService $documents)
    {
        $sortie = SortieCaisse::findOrFail($id);
        $documents->delete($sortie, $request->validated('motif'), $request->validated('strategie'), $request);

        return redirect()->route('sortie-caisses.index')
            ->with('success', 'Bon de sortie placé dans la corbeille.');
    }

public function valider(Request $request, $id, FinancialDocumentService $documents, DocumentNumberService $numbers, BudgetService $budgets)
{
    try {
        DB::transaction(function () use ($request, $id, $budgets, $numbers) {
            $sortie = SortieCaisse::with('lignesCloture')->lockForUpdate()->findOrFail($id);
            $typeBon = mb_strtoupper(trim((string) $request->input('type_bon', $sortie->type_bon)));
            if (! in_array($typeBon, ['BSC', 'BSB', 'BSM'], true)) {
                throw ValidationException::withMessages([
                    'type_bon' => 'La nature du bon est obligatoire : BSC, BSB ou BSM.',
                ]);
            }
            $typeTresorerie = match ($typeBon) {
                'BSB' => 'Banque',
                'BSM' => 'Mobile Money',
                default => 'Caisse',
            };
            $sortie->update([
                'numero' => $sortie->numero ?: $numbers->next('BSC', $sortie->date, $typeTresorerie),
                'type_bon' => $typeBon,
                'type' => $typeTresorerie,
                'statut'=>'Validé',
                'date_validation'=>now(),
                'valide_par'=>auth()->id(),
            ]);
            $budgets->realiserSortie($sortie);
            if ($sortie->origine === 'cloture') return;

            $nature = match ($sortie->type_bon) { 'BSM'=>'mobile_money', 'BSB'=>'banque', default=>'caisse' };
            $journalType = JournalType::with('compte')->where('est_tresorerie', true)
                ->where('nature', $nature)->where('monnaie', $sortie->monnaie)
                ->whereNotNull('liste_des_comptes_id')->first();
            if (! $journalType?->compte) throw ValidationException::withMessages(['journal'=>'Aucun journal '.$nature.' n’est configuré en '.$sortie->monnaie.'.']);
            $mode = match ($sortie->type_bon) { 'BSM'=>'mobile_money', 'BSB'=>'banque', default=>'espèces' };
            $tva = $sortie->appliquer_tva ? round((float)$sortie->montant_tva, 2) : 0;
            $principal = $tva > 0 ? round((float)$sortie->montant_ht, 2) : round((float)$sortie->montant, 2);
            $base = ['user_id'=>auth()->id(),'journal_type_id'=>$journalType->id,'reference'=>$sortie->numero,
                'date'=>$sortie->date,'monnaie'=>$sortie->monnaie,'mode_paiement'=>$mode,'statut'=>'En attente',
                'date_validation'=>null,'valide_par'=>null,'entrees_cdf'=>0,'entrees_usd'=>0];
            Journaux::updateOrCreate(['sortie_caisse_id'=>$sortie->id,'type'=>'depense','description'=>$sortie->motif], $base + [
                'liste_des_comptes_id'=>$journalType->liste_des_comptes_id,'sortie_caisse_id'=>$sortie->id,
                'description'=>$sortie->motif,'montant_ht'=>$principal,'taux_tva'=>0,'montant_tva'=>0,'montant_ttc'=>$principal,
                'sorties_cdf'=>$sortie->monnaie==='CDF'?$principal:0,'sorties_usd'=>$sortie->monnaie==='USD'?$principal:0]);
            if ($tva > 0) {
                $compteTva = ParametrageComptable::where('code','TVA_RECUPERABLE')->value('liste_des_comptes_id');
                if (!$compteTva) throw ValidationException::withMessages(['appliquer_tva'=>'Aucun compte TVA_RECUPERABLE n’est configuré.']);
                Journaux::updateOrCreate(['sortie_caisse_id'=>$sortie->id,'type'=>'tva','description'=>'TVA'], $base + [
                    'liste_des_comptes_id'=>$compteTva,'sortie_caisse_id'=>$sortie->id,'description'=>'TVA',
                    'montant_ht'=>0,'taux_tva'=>$sortie->taux_tva,'montant_tva'=>$tva,'montant_ttc'=>$tva,
                    'sorties_cdf'=>$sortie->monnaie==='CDF'?$tva:0,'sorties_usd'=>$sortie->monnaie==='USD'?$tva:0]);
            }
        });
        $sortie = SortieCaisse::findOrFail($id);
        if ($sortie->origine === 'cloture') return back()->with('success','Bon de sortie quotidien validé sans duplication de journal.');
        $route = match ($sortie->type_bon) { 'BSM'=>'journaux.create.mobile','BSB'=>'journaux.create.banque',default=>'journaux.create.caisse' };
        return back()->with('success','Bon de sortie validé et journal placé en attente.');
    } catch (ValidationException $e) {
        return back()->withErrors($e->errors());
    } catch (\Throwable $e) {
        Log::error('Échec de validation du Bon de sortie.', ['id'=>$id,'exception'=>$e]);
        return back()->with('error', $e->getMessage());
    }
}
public function rejeter(Request $request, $id, FinancialDocumentService $documents)
{
    try {
        DB::transaction(function () use ($id, $request, $documents) {
            $sortie = SortieCaisse::lockForUpdate()->findOrFail($id);
            if (config('features.budget') && $sortie->realisationBudgetaire()->exists()) {
                throw ValidationException::withMessages(['budget' => 'Ce Bon possède une réalisation budgétaire. Une contrepassation est requise avant son rejet.']);
            }
            $journauxQuery = $this->journauxDuBon($sortie);
            $journaux = (clone $journauxQuery)->lockForUpdate()->get();

            if ($journaux->contains(fn ($journal) => $this->statutEstValide($journal->statut))) {
                throw ValidationException::withMessages([
                    'statut' => 'Vous ne pouvez pas rejeter ce bon de sortie, car le statut du journal lié est déjà Validé.',
                ]);
            }

            $journaux->each(fn (Journaux $journal) => $documents->delete($journal, 'Suppression automatique lors du rejet du Bon de sortie.', 'cascade', $request));
            $sortie->update([
                'statut' => 'Rejeté',
                'date_validation' => null,
                'valide_par' => null,
            ]);
        });

        return back()->with('success', 'Bon de sortie rejeté.');
    } catch (ValidationException $e) {
        return back()->withErrors($e->errors());
    }
}
public function attente(Request $request, $id, FinancialDocumentService $documents)
{
    try {
        DB::transaction(function () use ($id, $request, $documents) {
            $sortie = SortieCaisse::lockForUpdate()->findOrFail($id);
            if (config('features.budget') && $sortie->realisationBudgetaire()->exists()) {
                throw ValidationException::withMessages(['budget' => 'Ce Bon possède une réalisation budgétaire. Une contrepassation est requise avant sa remise en attente.']);
            }
            $journauxQuery = $this->journauxDuBon($sortie);
            $journaux = (clone $journauxQuery)->lockForUpdate()->get();

            if ($journaux->contains(fn ($journal) => $this->statutEstValide($journal->statut))) {
                throw ValidationException::withMessages([
                    'statut' => 'Vous ne pouvez pas remettre ce bon de sortie en attente, car le statut du journal lié est déjà Validé.',
                ]);
            }

            $journaux->each(fn (Journaux $journal) => $documents->delete($journal, 'Suppression automatique lors de la remise en attente du Bon de sortie.', 'cascade', $request));
            $sortie->update([
                'statut' => 'En attente',
                'date_validation' => null,
                'valide_par' => null,
            ]);
        });

        return back()->with('success', 'Le bon de sortie a été remis en attente.');
    } catch (ValidationException $e) {
        return back()->withErrors($e->errors());
    }

}

public function reouvrir($id, WorkflowComptableService $workflow)
{
    $sortie = SortieCaisse::findOrFail($id);
    Gate::authorize('reouvrir', $sortie);
    $workflow->reouvrirSortieCaisse($sortie);

    return back()->with('success', 'Bon de sortie réouvert avec succès.');
}

private function journauxDuBon(SortieCaisse $sortie)
{
    return Journaux::query()->where(function ($query) use ($sortie) {
        $query->where('sortie_caisse_id', $sortie->id)
            ->orWhere('reference', $sortie->numero);
    });
}

private function donneesDocument($id): array
{
    $sortie = SortieCaisse::with(['user', 'etatBesoin.lignes', 'etatBesoin.departement'])->findOrFail($id);
    $entreprise = Entreprise::first();
    $logoData = null;
    if ($entreprise?->logo && Storage::disk('public')->exists($entreprise->logo)) {
        $mime = Storage::disk('public')->mimeType($entreprise->logo) ?: 'image/png';
        $logoData = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($entreprise->logo));
    }

    return compact('sortie', 'entreprise', 'logoData');
}

private function statutEstValide(?string $statut): bool
{
    return mb_strtolower(trim((string) $statut)) === 'validé';
}
}
