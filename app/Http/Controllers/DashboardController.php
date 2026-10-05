<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): mixed
    {
        $user = $request->user()->loadMissing('role');
        $validated = $request->validate(['mois' => ['nullable', 'date_format:Y-m']]);
        $month = \Carbon\CarbonImmutable::createFromFormat('!Y-m', $validated['mois'] ?? now()->format('Y-m'));
        $period = ['selectedMonth' => $month->format('Y-m'), 'monthLabel' => $month->locale('fr')->translatedFormat('F Y'),
            'monthFilters' => ['date_debut' => $month->toDateString(), 'date_fin' => $month->endOfMonth()->toDateString()]];

        if ($user->isTechnicalOfficer()) {
            $query = \App\Models\EtatBesoin::query()->perimetreTechnique()->whereBetween('date', [$month->toDateString(), $month->endOfMonth()->endOfDay()->toDateTimeString()]);

            return view('dashboard-technique', $period + [
                'total' => (clone $query)->count(),
                'statuts' => (clone $query)->select('statut')->selectRaw('COUNT(*) AS total')->groupBy('statut')->pluck('total', 'statut'),
                'sansPiece' => (clone $query)->sansPieceJustificative()->count(),
                'derniersSansPiece' => (clone $query)->sansPieceJustificative()->with('departement')->latest('date')->latest('id')->limit(10)->get(),
            ]);
        }

        if ($user->hasRole('Directeur Technique')) {
            return redirect()->route('etat-besoins.index');
        }

        return view('dashboard', $period + $dashboard->getData($user, $month));
    }
}
