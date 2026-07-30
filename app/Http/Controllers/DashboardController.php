<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): mixed
    {
        $user = $request->user()->loadMissing('role');

        return view('dashboard', $dashboard->getData($user));
    }
}
