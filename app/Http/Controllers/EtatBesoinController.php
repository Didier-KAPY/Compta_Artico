<?php

namespace App\Http\Controllers;

use App\Models\EtatBesoin;
use App\Models\EtatBesoinLigne;
use App\Models\Journaux;
use App\Models\Departement;
use Illuminate\Http\Request;
use App\Models\SortieCaisse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Services\WorkflowComptableService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Entreprise;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\DeleteFinancialDocumentRequest;
use App\Services\FinancialDocumentService;
use App\Services\DocumentNumberService;

class EtatBesoinController extends Controller
{
    /**
     * LISTE
     */
   public function index(Request $request)
{
    $query = EtatBesoin::with([
        'lignes',
        'user',
        'validateur',
        'departement'
    ]);

    $this->limiterAuDepartement($query);
    $peutVoirTout = auth()->user()->hasRole(['Super Admin', 'Admin', 'Gérant', 'Gerant']);
    if ($peutVoirTout && $request->filled('departement_id')) {
        $query->where('departement_id', $request->departement_id);
    }


    // Recherche par numéro
    if ($request->filled('numero')) {

        $query->where(
            'numero',
            'like',
            '%' . $request->numero . '%'
        );

    }


    // Date début
    if ($request->filled('date_debut')) {

        $query->whereDate(
            'date',
            '>=',
            $request->date_debut
        );

    }


    // Date fin
    if ($request->filled('date_fin')) {

        $query->whereDate(
            'date',
            '<=',
            $request->date_fin
        );

    }


    // Aucun filtre → aujourd'hui
    if (
        !$request->filled('numero') &&
        !$request->filled('date_debut') &&
        !$request->filled('date_fin') &&
        !$request->filled('departement_id')
    ) {

        $query->whereDate(
            'date',
            Carbon::today()
        );

    }


    $etatBesoins = $query
        ->latest()
        ->paginate(10)
        ->withQueryString();


    $departements = $peutVoirTout ? Departement::orderBy('designation')->get() : collect();

    return view(
        'etat_besoins.index',
        compact('etatBesoins', 'peutVoirTout', 'departements')
    );
}
    /**
     * FORM CREATE
     */
    public function create()
    {
        $departements = Departement::orderBy('designation')->get();
        return view('etat_besoins.create', compact('departements'));
    }

    /**
     * STORE AVEC LIGNES + NUMERO AUTO
     */
    public function store(Request $request, DocumentNumberService $numbers)
{
    $request->validate([
        'date' => 'required|date',
        'departement_id' => 'required|exists:departements,id',
        'demandeur' => 'required|string|max:255',
        'motif' => 'required|string',
        'monnaie' => 'required|in:CDF,USD',
    ]);

    DB::beginTransaction();

    try {

        $departement = Departement::findOrFail($request->departement_id);

        // Création de l'état de besoin
        $etat = EtatBesoin::create([
            'user_id' => auth()->id(),
            'departement_id' => $departement->id,
            'numero' => $numbers->next('EB', $request->date),
            'date' => $request->date,
            'service' => $departement->designation,
            'demandeur' => $request->demandeur,
            'motif' => $request->motif,
            'monnaie' => $request->monnaie,
            'montant_estime' => 0,
            'statut' => 'En attente',
        ]);

        $total = 0;

        // Enregistrement des lignes
        if ($request->filled('designation')) {

            foreach ($request->designation as $key => $designation) {

                if (!empty($designation)) {

                    $quantite = $request->quantite[$key] ?? 0;
                    $prixUnitaire = $request->prix_unitaire[$key] ?? 0;

                    $montant = $quantite * $prixUnitaire;

                    EtatBesoinLigne::create([
                        'etat_besoin_id' => $etat->id,
                        'designation' => $designation,
                        'quantite' => $quantite,
                        'prix_unitaire' => $prixUnitaire,
                        'montant' => $montant,
                        'monnaie' => $request->monnaie,
                    ]);

                    $total += $montant;
                }
            }
        }

        // Mise à jour du montant total
        $etat->update([
            'montant_estime' => $total
        ]);

        DB::commit();

        return redirect()
            ->route('etat-besoins.create')
            ->with('success', 'État de besoin créé avec succès.');

    } catch (\Exception $e) {

        DB::rollBack();

        return back()
            ->withInput()
            ->with('error', $e->getMessage());
    }
}

    /**
     * SHOW
     */
   public function show($id, FinancialDocumentService $documents)
    {
        $etat = $this->etatAccessible($id, ['lignes', 'departement', 'sortieCaisses.journaux.ecritures']);
        $suppressionDependencies = $documents->dependencies($etat);
        $documentLinks = collect();

        return view('etat_besoins.show', compact('etat', 'suppressionDependencies', 'documentLinks'));
    }

    public function imprimer($id)
    {
        return view('etat_besoins.document', $this->donneesDocument($id) + ['pdfMode' => false]);
    }

    public function telechargerPdf($id)
    {
        $data = $this->donneesDocument($id) + ['pdfMode' => true];
        $nom = preg_replace('/[^A-Za-z0-9_-]/', '-', $data['etat']->numero);

        return Pdf::loadView('etat_besoins.document', $data)
            ->setPaper('a4', 'portrait')
            ->download('etat-de-besoin-'.$nom.'.pdf');
    }

    /**
     * EDIT
     */
    public function edit(string $id)
    {
        $etat = $this->etatAccessible($id, ['lignes', 'departement']);
        abort_if($etat->statut !== 'En attente' && ! $this->estGestionnaire(request()->user()), 403);
        $this->verifierBonSortieNonValide($etat);

        $departements = Departement::orderBy('designation')->get();
        return view('etat_besoins.edit', compact('etat', 'departements'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'departement_id' => 'required|exists:departements,id',
            'demandeur' => 'required|string|max:255',
        ]);

        $etat = $this->etatAccessible($id);
        abort_if($etat->statut !== 'En attente' && ! $this->estGestionnaire($request->user()), 403);
        $this->verifierBonSortieNonValide($etat);
        $departement = Departement::findOrFail($request->departement_id);

        $etat->update([
            'departement_id' => $departement->id,
            'service' => $departement->designation,
            'demandeur' => $request->demandeur,
        ]);

        $etat->sortieCaisses()->update([
            'beneficiaire' => $etat->demandeur,
        ]);

        return redirect()
            ->route('etat-besoins.show', $etat->id)
            ->with('success', 'État de besoin modifié avec succès.');
    }

    /**
     * DELETE
     */
    public function destroy(DeleteFinancialDocumentRequest $request, $id, FinancialDocumentService $documents)
    {
        $etat = $this->etatAccessible($id);
        $documents->delete($etat, $request->validated('motif'), $request->validated('strategie'), $request);

        return redirect()
            ->route('etat-besoins.index')
            ->with('success', 'État de besoin placé dans la corbeille avec sa traçabilité complète.');
    }

public function valider(Request $request, $id, FinancialDocumentService $documents, DocumentNumberService $numbers)
{
    $etatAutorisation = $this->etatAccessible($id);
    Gate::authorize('valider', $etatAutorisation);

    $request->validate([
        'observation' => 'required|string',
        'action'      => 'required|in:valider,rejeter,attente',
        'monnaie'     => 'required|in:CDF,USD',
    ]);

    if ($request->user()->isManagement() && $request->action !== 'valider') {
        abort(403, 'Ce rôle peut uniquement valider un état de besoin.');
    }

    DB::beginTransaction();

    try {

        $etat = $this->etatAccessible($id);

        /*
        |--------------------------------------------------------------------------
        | REMETTRE EN ATTENTE
        |--------------------------------------------------------------------------
        */
        if ($request->action == 'attente') {
            $this->verifierBonSortieNonValide($etat);

            $etat->update([
                'observation' => $request->observation,
                'statut'      => 'En attente',
                'valide_par' => null,
                'date_validation' => null,
            ]);

            $sortie = SortieCaisse::where('etat_besoin_id', $etat->id)->first();

            if ($sortie) {
                $documents->delete($sortie, 'Suppression automatique lors de la remise en attente de l’état de besoin.', 'cascade', $request);
            }

            $message = "État de besoin remis en attente avec succès.";
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */
        elseif ($request->action == 'valider') {

            $etat->update([
                'observation' => $request->observation,
                'statut'      => 'Validé',
                'valide_par' => auth()->id(),
                'date_validation' => now(),
            ]);

            $sortieExiste = SortieCaisse::where(
                'etat_besoin_id',
                $etat->id
            )->exists();

            if (!$sortieExiste) {

                SortieCaisse::create([

                    'user_id'          => auth()->id(),
                    'numero'           => $numbers->next('BSC', $etat->date, 'Caisse'),
                    'date'             => $etat->date,
                    'etat_besoin_id'   => $etat->id,
                    'beneficiaire'     => $etat->demandeur,
                    'motif'            => $etat->motif,
                    'montant'          => $etat->montant_estime,
                    'monnaie'          => $etat->monnaie,
                    'observation'      => $request->observation,
                    'statut'           => 'En attente',

                ]);
            }

            $message = "État de besoin validé avec succès. Le bon de sortie caisse a été créé.";
        }

        /*
        |--------------------------------------------------------------------------
        | REJET
        |--------------------------------------------------------------------------
        */
        else {
            $this->verifierBonSortieNonValide($etat);

            $etat->update([
                'observation' => $request->observation,
                'statut'      => 'Rejeté',
                'valide_par' => null,
                'date_validation' => null,
            ]);

            $sortie = SortieCaisse::where('etat_besoin_id', $etat->id)->first();

            if ($sortie) {
                $documents->delete($sortie, 'Suppression automatique lors du rejet de l’état de besoin.', 'cascade', $request);
            }

            $message = "État de besoin rejeté avec succès.";
        }

        DB::commit();

        return redirect()
            ->route('etat-besoins.index')
            ->with('success', $message);

    } catch (\Exception $e) {

        DB::rollBack();

        return back()->with('error', $e->getMessage());
    }
}

public function reouvrir(Request $request, $id, WorkflowComptableService $workflow, FinancialDocumentService $documents)
{
    $etat = $this->etatAccessible($id);
    Gate::authorize('reouvrir', $etat);
    $workflow->reouvrirEtatBesoin($etat);
    $etat->sortieCaisses()->where('statut', '!=', 'Validé')->get()->each(
        fn (SortieCaisse $sortie) => $documents->delete(
            $sortie,
            'Suppression automatique lors de la réouverture de l’état de besoin.',
            'cascade',
            $request
        )
    );

    return back()->with('success', 'État de besoin réouvert avec succès.');
}

private function limiterAuDepartement($query): void
{
    $user = auth()->user();
    if ($user->hasRole(['Super Admin', 'Admin', 'Gérant', 'Gerant'])) {
        return;
    }

    if ($user->departement_id) {
        $query->where('departement_id', $user->departement_id);
    } else {
        // Compatibilité pour les utilisateurs non encore affectés.
        $query->where('user_id', $user->id);
    }
}

private function etatAccessible($id, array $relations = []): EtatBesoin
{
    $query = EtatBesoin::with($relations);
    $this->limiterAuDepartement($query);
    return $query->findOrFail($id);
}

private function donneesDocument($id): array
{
    $entreprise = Entreprise::first();
    $logoData = null;
    if ($entreprise?->logo && Storage::disk('public')->exists($entreprise->logo)) {
        $mime = Storage::disk('public')->mimeType($entreprise->logo) ?: 'image/png';
        $logoData = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($entreprise->logo));
    }

    return [
        'etat' => $this->etatAccessible($id, ['lignes', 'departement', 'user']),
        'entrepriseDocument' => $entreprise,
        'logoData' => $logoData,
    ];
}

private function verifierBonSortieNonValide(EtatBesoin $etat): void
{
    if ($etat->sortieCaisses()->where('statut', 'Validé')->exists()) {
        throw ValidationException::withMessages([
            'statut' => 'Action impossible : le bon de sortie lié est déjà validé. Remettez d’abord le bon de sortie en attente.',
        ]);
    }
}

private function estGestionnaire($user): bool
{
    return $user->hasRole(['Super Admin', 'Admin', 'Gérant', 'Gerant', 'Directeur Général']);
}
}
