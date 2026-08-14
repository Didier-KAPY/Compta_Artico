<?php

namespace App\Http\Middleware;

use App\Services\PeriodeComptableService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountingDateIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe() && $request->filled('date')) {
            app(PeriodeComptableService::class)->assertOpen($request->string('date')->toString());
        }

        return $next($request);
    }
}
