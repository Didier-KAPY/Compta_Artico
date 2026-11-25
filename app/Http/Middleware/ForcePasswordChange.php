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

        if ($user->statut !== 'Actif') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error_msg', 'Ce compte est désactivé. Contactez un administrateur.');
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
