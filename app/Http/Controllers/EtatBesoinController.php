<?php

namespace App\Http\Controllers;

use App\Models\EtatBesoin;
use App\Models\EtatBesoinLigne;
use App\Models\Departement;
use Illuminate\Http\Request;
use App\Models\SortieCaisse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Services\WorkflowComptableService;
use Carbon\Carbon;

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
    public function store(Request $request)
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
            'numero' => $this->generateNumero(),
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
   public function show($id)
    {
        $etat = $this->etatAccessible($id, ['lignes', 'departement']);

        if ($etat->statut === 'Validé') {
            return back()->with('error', 'Cet état validé doit d’abord être réouvert.');
        }

        return view('etat_besoins.show', compact('etat'));
    }

    /**
     * EDIT
     */
    public function edit(string $id)
    {
        $etat = $this->etatAccessible($id, ['lignes', 'departement']);

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
        $departement = Departement::findOrFail($request->departement_id);

        if ($etat->statut === 'Validé') {
            return back()->with('error', 'Cet état validé doit d’abord être réouvert.');
        }

        $etat->update([
            'departement_id' => $departement->id,
            'service' => $departement->designation,
            'demandeur' => $request->demandeur,
        ]);

        return redirect()
            ->route('etat-besoins.show', $etat->id)
            ->with('success', 'État de besoin modifié avec succès.');
    }

    /**
     * DELETE
     */
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $query = EtatBesoin::query();
            $this->limiterAuDepartement($query);
            $etat = $query->lockForUpdate()->findOrFail($id);
            if ($etat->statut === 'Validé') {
                throw ValidationException::withMessages(['statut' => 'Un état validé doit d’abord être réouvert.']);
            }
            if ($etat->sortieCaisses()->exists()) {
                throw ValidationException::withMessages(['dependance' => 'Suppression impossible : un Bon de sortie est lié.']);
            }
            $etat->delete();
        });

        return redirect()
            ->route('etat-besoins.index')
            ->with('success', 'État de besoin supprimé avec succès.');
    }

    /**
     * GENERATEUR NUMERO AUTO
     * Format: EB-0001-26-06
     */
    private function generateNumero()
    {
        $year = date('y');
        $month = date('m');

        $last = EtatBesoin::orderBy('id', 'desc')->first();

        if (!$last) {
            $num = 1;
        } else {
            $parts = explode('-', $last->numero);
            $num = isset($parts[1]) ? intval($parts[1]) + 1 : 1;
        }

        return 'EB-' . str_pad($num, 4, '0', STR_PAD_LEFT) . '-' . $year . '-' . $month;
    }

    private function generateNumeroSortie()
{
    $year = date('y');
    $month = date('m');
    $last = SortieCaisse::latest('id')->first();

    $number = $last ? $last->id + 1 : 1;

    return 'BSC-' . $year . '-' . $month . '-'. str_pad($number, 4, '0', STR_PAD_LEFT);
}
public function valider(Request $request, $id)
{
    $etatAutorisation = $this->etatAccessible($id);
    Gate::authorize('valider', $etatAutorisation);

    $request->validate([
        'observation' => 'required|string',
        'action'      => 'required|in:valider,rejeter,attente',
        'monnaie'     => 'required|in:CDF,USD',
    ]);

    DB::beginTransaction();

    try {

        $etat = $this->etatAccessible($id);

        /*
        |--------------------------------------------------------------------------
        | REMETTRE EN ATTENTE
        |--------------------------------------------------------------------------
        */
        if ($request->action == 'attente') {

            $sortieValidee = SortieCaisse::where('etat_besoin_id', $etat->id)
                ->where('statut', 'Validé')
                ->exists();

            if ($sortieValidee) {

                DB::rollBack();

                return back()->with(
                    'error',
                    'Vous ne pouvez pas remettre cet état de besoin en attente, car le bon de sortie caisse a déjà été validé. Veuillez d\'abord remettre le bon de sortie en attente.'
                );
            }

            $etat->update([
                'observation' => $request->observation,
                'statut'      => 'En attente',
            ]);

            $sortie = SortieCaisse::where('etat_besoin_id', $etat->id)->first();

            if ($sortie) {
                $sortie->delete();
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
            ]);

            $sortieExiste = SortieCaisse::where(
                'etat_besoin_id',
                $etat->id
            )->exists();

            if (!$sortieExiste) {

                SortieCaisse::create([

                    'user_id'          => auth()->id(),
                    'numero'           => $this->generateNumeroSortie(),
                    'date'             => now(),
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

            $sortieValidee = SortieCaisse::where('etat_besoin_id', $etat->id)
                ->where('statut', 'Validé')
                ->exists();

            if ($sortieValidee) {

                DB::rollBack();

                return back()->with(
                    'error',
                    'Vous ne pouvez pas rejeter cet état de besoin, car le bon de sortie caisse a déjà été validé. Veuillez d\'abord remettre le bon de sortie en attente.'
                );
            }

            $etat->update([
                'observation' => $request->observation,
                'statut'      => 'Rejeté',
            ]);

            $sortie = SortieCaisse::where('etat_besoin_id', $etat->id)->first();

            if ($sortie) {
                $sortie->delete();
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

public function reouvrir($id, WorkflowComptableService $workflow)
{
    $etat = $this->etatAccessible($id);
    Gate::authorize('reouvrir', $etat);
    $workflow->reouvrirEtatBesoin($etat);

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
}
