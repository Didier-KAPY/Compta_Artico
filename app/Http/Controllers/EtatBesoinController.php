<?php

namespace App\Http\Controllers;

use App\Models\EtatBesoin;
use App\Models\EtatBesoinLigne;
use Illuminate\Http\Request;
use App\Models\SortieCaisse;
use Illuminate\Support\Facades\DB;
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
        'user'
    ]);


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
        !$request->filled('date_fin')
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


    return view(
        'etat_besoins.index',
        compact('etatBesoins')
    );
}
    /**
     * FORM CREATE
     */
    public function create()
    {
        return view('etat_besoins.create');
    }

    /**
     * STORE AVEC LIGNES + NUMERO AUTO
     */
    public function store(Request $request)
{
    $request->validate([
        'date' => 'required|date',
        'service' => 'required|string|max:255',
        'demandeur' => 'required|string|max:255',
        'motif' => 'required|string',
        'monnaie' => 'required|in:CDF,USD',
    ]);

    DB::beginTransaction();

    try {

        // Création de l'état de besoin
        $etat = EtatBesoin::create([
            'user_id' => auth()->id(),
            'numero' => $this->generateNumero(),
            'date' => $request->date,
            'service' => $request->service,
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
        $etat = EtatBesoin::with('lignes')->findOrFail($id);

        return view('etat_besoins.show', compact('etat'));
    }

    /**
     * EDIT
     */
    public function edit(string $id)
    {
        $etat = EtatBesoin::with('lignes')->findOrFail($id);

        return view('etat_besoins.edit', compact('etat'));
    }

    /**
     * DELETE
     */
    public function destroy($id)
    {
        $etat = EtatBesoin::findOrFail($id);
        $etat->delete();

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
    $request->validate([
        'observation' => 'required|string',
        'action'      => 'required|in:valider,rejeter,attente',
        'monnaie'     => 'required|in:CDF,USD',
    ]);

    DB::beginTransaction();

    try {

        $etat = EtatBesoin::findOrFail($id);

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
}