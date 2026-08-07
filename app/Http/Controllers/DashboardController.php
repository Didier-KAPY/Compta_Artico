<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): mixed
    {
        $user = $request->user()->loadMissing('role');

        if ($user->hasRole('Directeur Technique')) {
            return redirect()->route('etat-besoins.index');
        }

        return view('dashboard', $dashboard->getData($user));
    }
}
