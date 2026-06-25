<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        if ($user->password_default == 1) {
            // Eviter boucle infinie
            if (!$request->is('password-change')) {
                return redirect()->route('password.change')
                    ->with('warning', 'Veuillez changer votre mot de passe avant de continuer.');
            }
        }

        return $next($request);
    }
}