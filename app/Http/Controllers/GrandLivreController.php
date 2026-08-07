<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EcritureComptable;
use App\Models\ListeDesComptes;
use Carbon\Carbon;

class GrandLivreController extends Controller
{

 public function index(Request $request)
{
    $request->validate([
        'mois' => ['nullable', 'date_format:Y-m'],
    ]);

    $moisSelectionne = $request->filled('mois')
        ? Carbon::createFromFormat('Y-m', $request->input('mois'))->startOfMonth()
        : now()->startOfMonth();
    $dateDebut = $moisSelectionne->copy()->startOfMonth();
    $dateFin = $moisSelectionne->copy()->endOfMonth();

    $comptes = ListeDesComptes::orderBy('compte')->get();
    $compteSelectionne = null;
    $resume = [
        'initial_debit' => 0,
        'initial_credit' => 0,
        'mouvement_debit' => 0,
        'mouvement_credit' => 0,
        'final_debit' => 0,
        'final_credit' => 0,
    ];

    // Par défaut : aucune écriture
    $ecritures = EcritureComptable::query()->whereRaw('1 = 0')->paginate(20);

    $compteId = $request->input('liste_des_comptes_id');
    if (! $compteId && $request->filled('compte')) {
        $compteId = ListeDesComptes::where('compte', $request->input('compte'))->value('id');
    }

    // Le mois sélectionné constitue la période courante du Grand Livre.
    if ($compteId) {

        $compteSelectionne = ListeDesComptes::find($compteId);

        $query = EcritureComptable::with([
            'compte',
            'journal'
        ])
        ->where('statut', 'Validé')
        ->where('liste_des_comptes_id', $compteId)
        ->whereRaw('(COALESCE(debit_cdf, 0) <> 0 OR COALESCE(credit_cdf, 0) <> 0)');

        $initial = EcritureComptable::query()
            ->where('statut', 'Validé')
            ->where('liste_des_comptes_id', $compteId)
            ->whereDate('date', '<', $dateDebut->toDateString())
            ->selectRaw('COALESCE(SUM(debit_cdf), 0) AS debit, COALESCE(SUM(credit_cdf), 0) AS credit')
            ->first();

        // Filtre par période
        $query->whereBetween('date', [
            $dateDebut->toDateString(),
            $dateFin->toDateString()
        ]);

        $mouvements = (clone $query)
            ->selectRaw('COALESCE(SUM(debit_cdf), 0) AS debit, COALESCE(SUM(credit_cdf), 0) AS credit')
            ->first();

        $soldeInitial = (float) $initial->debit - (float) $initial->credit;
        $soldeFinal = $soldeInitial + (float) $mouvements->debit - (float) $mouvements->credit;
        $resume = [
            'initial_debit' => max($soldeInitial, 0),
            'initial_credit' => max(-$soldeInitial, 0),
            'mouvement_debit' => (float) $mouvements->debit,
            'mouvement_credit' => (float) $mouvements->credit,
            'final_debit' => max($soldeFinal, 0),
            'final_credit' => max(-$soldeFinal, 0),
        ];

        $ecritures = (clone $query)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(20)
            ->withQueryString();

        // Le solde affiché sur chaque ligne part du report antérieur et évolue
        // avec les mouvements du mois. Sur les pages suivantes, on reprend le
        // solde atteint juste avant la première écriture de la page.
        $soldeProgressif = $soldeInitial;
        $premiereEcriture = $ecritures->first();

        if ($premiereEcriture && $ecritures->currentPage() > 1) {
            $mouvementsAvantPage = (clone $query)
                ->where(function ($avantPage) use ($premiereEcriture) {
                    $avantPage
                        ->whereDate('date', '<', $premiereEcriture->date->toDateString())
                        ->orWhere(function ($memeDate) use ($premiereEcriture) {
                            $memeDate
                                ->whereDate('date', $premiereEcriture->date->toDateString())
                                ->where('id', '<', $premiereEcriture->id);
                        });
                })
                ->selectRaw('COALESCE(SUM(debit_cdf), 0) AS debit, COALESCE(SUM(credit_cdf), 0) AS credit')
                ->first();

            $soldeProgressif += (float) $mouvementsAvantPage->debit
                - (float) $mouvementsAvantPage->credit;
        }

        $ecritures->getCollection()->each(function ($ecriture) use (&$soldeProgressif) {
            $soldeProgressif += (float) $ecriture->debit_cdf - (float) $ecriture->credit_cdf;
            $ecriture->solde_debiteur = max($soldeProgressif, 0);
            $ecriture->solde_crediteur = max(-$soldeProgressif, 0);
        });

    }

    return view(
        'comptabilite.grandlivre.index',
        compact(
            'ecritures',
            'comptes',
            'compteSelectionne',
            'resume',
            'moisSelectionne',
            'dateDebut',
            'dateFin'
        )
    );
}

}
