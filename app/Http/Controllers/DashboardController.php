<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function data(Request $request)
{
    $year = $request->year ?? date('Y');

    $totalEntrees = EntreeCaisse::whereYear('created_at', $year)->count();
    $enAttente = EntreeCaisse::whereYear('created_at', $year)->where('status', 'pending')->count();
    $totalValidees = EntreeCaisse::whereYear('created_at', $year)->where('status', 'validé')->count();
    $totalRejetees = EntreeCaisse::whereYear('created_at', $year)->where('status', 'rejeté')->count();

    // évolution mensuelle
    $labels = [];
    $values = [];

    for ($m = 1; $m <= 12; $m++) {
        $labels[] = date("M", mktime(0, 0, 0, $m, 1));
        $values[] = EntreeCaisse::whereYear('created_at', $year)
            ->whereMonth('created_at', $m)
            ->count();
    }

    return response()->json([
        'totalEntrees' => $totalEntrees,
        'enAttente' => $enAttente,
        'validees' => $totalValidees,
        'rejetees' => $totalRejetees,
        'labels' => $labels,
        'values' => $values,
    ]);
}
}
