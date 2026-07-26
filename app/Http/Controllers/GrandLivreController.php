<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EcritureComptable;
use App\Models\ListeDesComptes;

class GrandLivreController extends Controller
{

 public function index(Request $request)
{
    $comptes = ListeDesComptes::all();

    // Par défaut : aucune écriture
    $ecritures = collect();

    // Exécuter la recherche uniquement si les deux dates sont renseignées
    if ($request->filled('date_debut') && $request->filled('date_fin')) {

        $query = EcritureComptable::with([
            'compte',
            'journal'
        ])
        ->where('statut', 'Validé');

        // Filtre par compte
        if ($request->filled('liste_des_comptes_id')) {
            $query->where(
                'liste_des_comptes_id',
                $request->liste_des_comptes_id
            );
        }

        // Filtre par période
        $query->whereBetween('date', [
            $request->date_debut,
            $request->date_fin
        ]);

        $ecritures = $query
            ->orderBy('date', 'asc')
            ->get();
    }

    return view(
        'comptabilite.grandlivre.index',
        compact(
            'ecritures',
            'comptes'
        )
    );
}

}