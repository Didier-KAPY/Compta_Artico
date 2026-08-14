<?php

namespace App\Http\Controllers;


use App\Http\Requests\AuthRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

use App\Models\User;



class AuthController extends Controller
{
    /**
     * Page login
     */
    public function login()
    {
        return view('auth.login');
    }

    /**
     * Traitement connexion
     */
    public function handlelogin(AuthRequest $request)
    {

        $throttleKey = mb_strtolower((string) $request->input('email')).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($throttleKey).' seconde(s).',
            ]);
        }

        $credentials = $request->only(
            'email',
            'password'
        );

        // Vérifier utilisateur
        $user = User::with('role')
            ->where(
                'email',
                $credentials['email']
            )
            ->first();
        if (! $user || $user->statut !== 'Actif')
        {

            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withInput()
                ->with(
                    'error_msg',
                    'Identifiants incorrects.'
                );
        }
        // Connexion

        if(Auth::attempt($credentials))
        {
            RateLimiter::clear($throttleKey);
            $request->session()
                ->regenerate();
            $user = Auth::user()
                ->load('role');
            $user->last_logged_in = now();
            $user->save();

           // première connexion
            if($user->password_default == true)
            {
                return redirect()
                    ->route(
                        'password.change'
                    );
            }

            return $this->authenticated(
                $user
               );
        }
        RateLimiter::hit($throttleKey, 60);
        return back()
            ->withInput()
            ->with(
                'error_msg',
                'Identifiants incorrects.'
            );
    }
    
    /**
 * Redirection après connexion
 */
protected function authenticated($user)
{
    $role = strtolower(
        $user->role?->designation ?? ''
    );


    // Comptable et DAF → Caisses par défaut
    if ($user->isAccounting()) {

        return redirect()
            ->route('journaux.index');
    }


    // Caissier et Trésorier → Caisses par défaut
    if(in_array($role, [
        'caissier',
        'caissière',
        'trésorier',
        'trésorière'
    ])) {

        return redirect()
            ->route('journaux.index');
    }


    // Direction technique et chefs → États de besoin par défaut.
    if ($user->hasRole([
        'Chef de Service',
        'Chef de Département',
        'Directeur Technique',
    ])) {

        return redirect()
            ->route('etat-besoins.index');
    }


    // Administration → Dashboard
    return redirect()
        ->route('dashboard');
}
    /**
     * Page succès
     */
    public function loginSucces()
    {
        return view(
            'auth.succes'
        );
    }
    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()
            ->invalidate();
        $request->session()
            ->regenerateToken();
        return redirect()
            ->route('login')
            ->with(
                'success_msg',
                'Déconnexion réussie.'
            );
    }
    /**
     * Formulaire changement password
     */
    public function showChangePasswordForm()
    {
        return view(
            'auth.change-password'
        );
    }

    /**
     * Changement password
     */
    public function updatePassword(
        Request $request
    )
    {

        $request->validate([
            'password'=>
                'required|string|min:8|confirmed'

        ]);

        $user = Auth::user();
        $user->password =
            Hash::make(
                $request->password
            );
        $user->password_default = false;
        $user->save();

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'Mot de passe changé avec succès.'
            );

    }
    /**
     * Dashboard
     */
    public function index()
    {
        return view(
            'dashboard'
        );
    }
}
