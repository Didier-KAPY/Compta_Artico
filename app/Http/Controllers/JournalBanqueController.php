<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TauxDeChange;
use App\Models\ListeDesComptes;
use App\Models\Journaux;
use Carbon\Carbon;

class JournalBanqueController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | FORMULAIRE JOURNAL BANQUE
    |--------------------------------------------------------------------------
    */
    public function journalBanque()
    {

        // COMPTE BANQUE
        $comptes = ListeDesComptes::where('compte', 'like', '%521%')->get();

        // TOUS LES COMPTES
        $tousLesComptes = ListeDesComptes::all();

        // DERNIER TAUX
        $taux = TauxDeChange::latest()->first();

        return view('tresorerie.JournalBanque', compact(
            'comptes',
            'tousLesComptes',
            'taux'
        ));
    }


   public function store(Request $request)
{
    $request->validate([
        'journal_type_id' => 'required',
        'liste_des_comptes_id' => 'required',
        'date' => 'required',
        'action' => 'required',
    ]);

    $taux = TauxDeChange::find($request->taux_de_change_id);
    $valeur = $taux?->taux_de_change ?? 1;

    $entreesCdf = $request->entrees_cdf ?? 0;
    $entreesUsd = $request->entrees_usd ?? 0;

    $sortiesCdf = $request->sorties_cdf ?? 0;
    $sortiesUsd = $request->sorties_usd ?? 0;

    $totalEntree = $entreesCdf + ($entreesUsd * $valeur);
    $totalSortie = $sortiesCdf + ($sortiesUsd * $valeur);

    $prefix = "ART-" . date('my') . "-";

    $last = Journaux::where('numero', 'like', $prefix . '%')
        ->orderBy('id', 'desc')
        ->first();

    $num = $last ? intval(substr($last->numero, -3)) + 1 : 1;

    $numero = $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);

    Journaux::create([
        'user_id' => auth()->id(),
        'journal_type_id' => $request->journal_type_id,
        'liste_des_comptes_id' => $request->liste_des_comptes_id,
        'clients' => $request->clients,
        'contacts' => $request->contacts,
        'input' => $request->journal_type_id,
        'taux_de_change_id' => $request->taux_de_change_id,
        'date' => $request->date,
        'description' => $request->description,
        'numero' => $numero,
        'entrees_cdf' => $entreesCdf,
        'entrees_usd' => $entreesUsd,
        'sorties_cdf' => $sortiesCdf,
        'sorties_usd' => $sortiesUsd,
        'total_entree_cdf' => $totalEntree,
        'total_sortie_cdf' => $totalSortie,
    ]);

    return back()->with('success', 'Enregistrement réussi !');
}



    /*
|--------------------------------------------------------------------------
| LISTE JOURNAL BANQUE
|--------------------------------------------------------------------------
*/
public function listeJournalBanque(Request $request)
{
    $from = $request->from ?? now()->toDateString();
    $to   = $request->to ?? now()->toDateString();

    // =========================
    // BASE QUERY (NE PAS PAGINER ICI)
    // =========================
    $baseQuery = Journaux::join('liste_des_comptes', 'journaux.input', '=', 'liste_des_comptes.id')
        ->where('liste_des_comptes.compte', 'LIKE', '%521%')
        ->whereBetween('journaux.date', [$from, $to]);

    // =========================
    // LISTE PAGINÉE
    // =========================
    $journaux = (clone $baseQuery)
        ->select('journaux.*')
        ->orderBy('journaux.id', 'desc')
        ->paginate(10)
        ->withQueryString();

    // =========================
    // TOTAUX
    // =========================
    $totalEntreeCdf = (clone $baseQuery)->sum('entrees_cdf');
    $totalSortieCdf = (clone $baseQuery)->sum('sorties_cdf');

    $totalEntreeUsd = (clone $baseQuery)->sum('entrees_usd');
    $totalSortieUsd = (clone $baseQuery)->sum('sorties_usd');

    $totalEntree = (clone $baseQuery)->sum('total_entree_cdf');
    $totalSortie = (clone $baseQuery)->sum('total_sortie_cdf');

    // =========================
    // SOLDES
    // =========================
    $soldeCdf = $totalEntreeCdf - $totalSortieCdf;
    $soldeUsd = $totalEntreeUsd - $totalSortieUsd;
    $soldeTotal = $totalEntree - $totalSortie;

    return view('tresorerie.liste-journal-banque', compact(
        'journaux',
        'from',
        'to',
        'totalEntreeCdf',
        'totalSortieCdf',
        'totalEntreeUsd',
        'totalSortieUsd',
        'totalEntree',
        'totalSortie',
        'soldeCdf',
        'soldeUsd',
        'soldeTotal'
    ));
}
}