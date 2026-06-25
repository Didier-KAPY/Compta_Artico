<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SortieCaisse;
use Illuminate\Support\Facades\DB;
use App\Models\Journaux;
use Carbon\Carbon;

class SortieCaisseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
 public function index(Request $request)
{
    $query = SortieCaisse::with('etatBesoin');

    // Recherche par numéro
    if ($request->filled('numero')) {
        $query->where('numero', 'like', '%' . $request->numero . '%');
    }

    // Date début
    if ($request->filled('date_debut')) {
        $query->whereDate('date', '>=', $request->date_debut);
    }

    // Date fin
    if ($request->filled('date_fin')) {
        $query->whereDate('date', '<=', $request->date_fin);
    }

    // Afficher uniquement les sorties du jour si aucun filtre
    if (
        !$request->filled('numero') &&
        !$request->filled('date_debut') &&
        !$request->filled('date_fin')
    ) {
        $query->whereDate('date', Carbon::today());
    }

    $sorties = $query
        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view('sortie_caisses.index', compact('sorties'));
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'beneficiaire' => 'required|string|max:255',
            'motif' => 'required|string',
            'montant' => 'required|numeric|min:0',
            'monnaie' => 'required|in:CDF,USD',
            'type' => 'required|in:Caisse,Banque,Monnaie électronique',
            'observation' => 'required|string',
        ]);

        SortieCaisse::create([
            'numero' => 'BS-' . time(),
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

        return redirect()
            ->route('sortie-caisses.index')
            ->with('success', 'Bon de sortie créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $sortie = SortieCaisse::with('user')
            ->findOrFail($id);

        return view('sortie_caisses.show', compact('sortie'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    private function generateNumero()
{
    $annee = date('y');
    $mois = date('m');

    $base = $annee . $mois; // Exemple : 2606

    $last = SortieCaisse::where('numero', 'like', $base . '%')
        ->orderByDesc('id')
        ->first();

    $next = 1;

    if ($last) {
        // Exemple : 26060025
        $lastNumber = intval(substr($last->numero, 4));

        $next = $lastNumber + 1;
    }

    return $base . str_pad($next, 4, '0', STR_PAD_LEFT);
}
    /**
     * Update the specified resource in storage.
     */
 public function update(Request $request, string $id)
{
    $request->validate([
        'date' => 'required|date',
        'beneficiaire' => 'required|string|max:255',
        'motif' => 'required|string',
        'montant' => 'required|numeric|min:0',
        'monnaie' => 'required|in:CDF,USD',
        'observation' => 'required|string',
    ]);

    $sortie = SortieCaisse::findOrFail($id);

    // Générer un numéro seulement si absent
    if (empty($sortie->numero)) {
        $sortie->numero = $this->generateNumero();
    }

    $sortie->date = $request->date;
    $sortie->beneficiaire = $request->beneficiaire;
    $sortie->motif = $request->motif;
    $sortie->montant = $request->montant;
    $sortie->monnaie = $request->monnaie;
    $sortie->observation = $request->observation;

    $sortie->save();

    return redirect()
        ->route('sortie-caisses.show', $sortie->id)
        ->with('success', 'Bon de sortie modifié avec succès.');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

public function valider(Request $request, $id)
{
    DB::beginTransaction();
    try {
        $sortie = SortieCaisse::findOrFail($id);
        // Générer le numéro si absent
        if (empty($sortie->numero)) {
            $sortie->numero = $this->generateNumero();
            $sortie->save();
        }

        $montant = $sortie->montant ?? 0;
        /*
        |--------------------------------------------------------------------------
        | REMETTRE EN ATTENTE
        |--------------------------------------------------------------------------
        */
        if ($sortie->statut === 'Validé') {
            $journal = Journaux::where('reference', $sortie->numero)->first();
            // Impossible si journal déjà validé
            if ($journal && $journal->statut === 'Validé') {
                DB::rollBack();
                return back()->with(
                    'error',
                    'Impossible de remettre ce bon en attente car le journal est déjà validé.'
                );
            }


            // Supprimer le journal en attente
            if ($journal) {
                $journal->delete();
            }


            $sortie->update([
                'statut' => 'En attente',
                'date_validation' => null,
                'valide_par' => null,
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
            'statut' => 'Validé',
            'date_validation' => now(),
            'valide_par' => auth()->id(),
        ]);

        Journaux::updateOrCreate(
            [
                'reference' => $sortie->numero
            ],
            [
                'user_id' => auth()->id(),
                'sortie_caisse_id' => $sortie->id,
                'date' => $sortie->date,
                'reference' => $sortie->numero,
                'description' => $sortie->motif,
                'piece_justificatif' => $sortie->numero,
                'mode_paiement' => 'Espèces',
                'monnaie' => $sortie->monnaie,
                'entrees_cdf' => 0,
                'entrees_usd' => 0,
                'sorties_cdf' => $sortie->monnaie == 'CDF'
                    ? $sortie->montant
                    : 0,
                'sorties_usd' => $sortie->monnaie == 'USD'
                    ? $sortie->montant
                    : 0,
                'statut' => 'En attente',
            ]
    );


        DB::commit();


        return redirect()
            ->route('sortie-caisses.index')
            ->with(
                'success',
                'Bon de sortie validé avec succès.'
            );


    } catch (\Exception $e) {

        DB::rollBack();

         dd([
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
    }
}

public function rejeter($id)
{
    $sortie = SortieCaisse::findOrFail($id);

    $sortie->update([
        'statut' => 'Rejeté'
    ]);

    return redirect()
        ->route('sortie-caisses.show', $id)
        ->with('success', 'Bon de sortie rejeté.');
}
    
}
