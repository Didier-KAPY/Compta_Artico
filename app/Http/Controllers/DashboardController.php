<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): mixed
    {
        $user = $request->user()->loadMissing('role');

        if ($user->isTechnicalOfficer()) {
            $query = \App\Models\EtatBesoin::query()->perimetreTechnique();

            return view('dashboard-technique', [
                'total' => (clone $query)->count(),
                'statuts' => (clone $query)->select('statut')->selectRaw('COUNT(*) AS total')->groupBy('statut')->pluck('total', 'statut'),
                'sansPiece' => (clone $query)->sansPieceJustificative()->count(),
                'derniersSansPiece' => (clone $query)->sansPieceJustificative()->with('departement')->latest('date')->latest('id')->limit(10)->get(),
            ]);
        }

        if ($user->hasRole('Directeur Technique')) {
            return redirect()->route('etat-besoins.index');
        }

        return view('dashboard', $dashboard->getData($user));
    }
}
