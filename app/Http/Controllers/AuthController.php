<?php

namespace App\Http\Controllers;


use App\Http\Requests\AuthRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        if(!$user)
        {

            return back()
                ->withInput()
                ->with(
                    'error_msg',
                    'Utilisateur inexistant.'
                );
        }
        // Connexion

        if(Auth::attempt($credentials))
        {
            $request->session()
                ->regenerate();
            $user = Auth::user()
                ->load('role');
            // activation
            $user->statut = 'Actif';
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
        return back()
            ->withInput()
            ->with(
                'error_msg',
                'Email ou mot de passe incorrect.'
            );
    }
    /**
     * Redirection selon rôle
     */
    protected function authenticated($user)
    {
        $role = strtolower(
            $user->role?->designation ?? ''
        );
        switch($role)
        {
            case 'super admin':
                return redirect()
                    ->route('dashboard');
            case 'caissier':
                return redirect()
                    ->route(
                        'dashboard'
                    );

            case 'comptable':

                return redirect()
                    ->route(
                        'layouts.partials.etat-besoins',
                        'layouts.partials.journaux'
                    );

            default:
                return redirect()
                    ->route('login')
                    ->with(
                        'error_msg',
                        'Aucun rôle attribué.'
                    );

        }

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