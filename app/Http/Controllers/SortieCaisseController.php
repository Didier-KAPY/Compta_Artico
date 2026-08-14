<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteFinancialDocumentRequest;
use App\Services\FinancialDocumentService;

use Illuminate\Http\Request;
use App\Models\SortieCaisse;
use App\Models\JournalType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Services\WorkflowComptableService;
use App\Services\BudgetService;
use App\Models\Journaux;
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
            ->when($request->filled('date_fin'), fn ($query) => $query->whereDate('date', '<=', $request->date_fin))
            ->when(!$request->filled('numero') && !$request->filled('date_debut') && !$request->filled('date_fin'),
                fn ($query) => $query->whereDate('date', Carbon::today()));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('sortie_caisses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, DocumentNumberService $numbers)
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

        DB::transaction(function () use ($request, $numbers) {
            SortieCaisse::create([
                'numero' => $numbers->next('BSC', $request->date, $request->type),
                'date' => $request->date,
                'beneficiaire' => $request->beneficiaire,
                'motif' => $request->motif,
                'montant' => $request->montant,
                'monnaie' => $request->monnaie,
                'type' => $request->type,
                'observation' => $request->observation,
                'statut' => 'En attente',
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('sortie-caisses.index')
            ->with('success', 'Bon de sortie créé avec succès.');
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
    


    DB::beginTransaction();

    try {


        $sortie = SortieCaisse::findOrFail($id);



        // Générer le numéro si absent
        if(empty($sortie->numero)) {

            $sortie->numero = $numbers->next('BSC', $sortie->date, $sortie->type);
            $sortie->save();

        }



        /*
        |--------------------------------------------------------------------------
        | REMETTRE EN ATTENTE
        |--------------------------------------------------------------------------
        */

        if($sortie->statut === 'Validé') {

            if (config('features.budget') && $sortie->realisationBudgetaire()->exists()) {
                throw ValidationException::withMessages(['budget' => 'Ce Bon possède déjà une réalisation budgétaire. Une contrepassation budgétaire est requise avant sa réouverture.']);
            }


            $journal = Journaux::where(
                'reference',
                $sortie->numero
            )->first();



            if($journal && $journal->statut === 'Validé') {


                DB::rollBack();


                return back()->with(
                    'error',
                    'Impossible de remettre ce bon en attente car le journal est déjà validé.'
                );

            }



            if($journal){
                $documents->delete($journal, 'Suppression automatique lors de la remise en attente du Bon de sortie.', 'cascade', $request);

            }



            $sortie->update([

                'statut'=>'En attente',

                'date_validation'=>null,

                'valide_par'=>null,

            ]);



            DB::commit();



            return redirect()

                ->route('sortie-caisses.index')

                ->with(
                    'success',
                    'Le bon a été remis en attente.'
                );

        }





        /*
        |--------------------------------------------------------------------------
        | VALIDATION DU BON
        |--------------------------------------------------------------------------
        */


        $sortie->update([

            'statut'=>'Validé',

            'date_validation'=>now(),

            'valide_par'=>auth()->id(),

        ]);

        $budgets->realiserSortie($sortie);

        if ($sortie->origine === 'cloture') {
            DB::commit();
            return redirect()->route('sortie-caisses.index')
                ->with('success', 'Bon de sortie quotidien validé sans duplication de journal.');
        }





        Journaux::updateOrCreate(

            [

                'reference'=>$sortie->numero

            ],


            [

                'user_id'=>auth()->id(),


                // AJOUT IMPORTANT
                'journal_type_id'=>$request->journal_type_id,


                'sortie_caisse_id'=>$sortie->id,


                'date'=>$sortie->date,


                'description'=>$sortie->motif,


                'piece_justificatif'=>$sortie->numero,

                'type'=>'depense',
                'mode_paiement'=>'espèces',


                'monnaie'=>$sortie->monnaie,


                'entrees_cdf'=>0,

                'entrees_usd'=>0,


                'sorties_cdf'=>$sortie->monnaie == 'CDF'
                    ? $sortie->montant
                    : 0,


                'sorties_usd'=>$sortie->monnaie == 'USD'
                    ? $sortie->montant
                    : 0,


                'statut'=>'En attente',

            ]

        );



        DB::commit();



        return redirect()

            ->route('sortie-caisses.index')

            ->with(
                'success',
                'Bon de sortie validé avec succès.'
            );



    } catch(\Exception $e) {


        DB::rollBack();


        Log::error('Échec de la validation du bon de sortie.', [
            'sortie_caisse_id' => $id,
            'user_id' => Auth::id(),
            'exception' => $e,
        ]);

        return back()->with(
            'error',
            'La validation du bon de sortie a échoué. Veuillez réessayer ou contacter un administrateur.'
        );

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
