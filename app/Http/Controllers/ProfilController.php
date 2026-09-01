<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Role;
use App\Models\Departement;
use App\Models\Fonction;
use App\Models\Entreprise;


class ProfilController extends Controller
{


    public function index()
    {

        $user = Auth::user()->load(['role', 'departement', 'fonction']);
        $entreprise = Entreprise::first();

        return view('profil.index', compact('user', 'entreprise'));

    }





    public function createUser()
    {

        $roles = Role::orderBy('designation')
            ->get();

        $departements = Departement::orderBy('designation')->get();
        $fonctions = Fonction::orderBy('designation')->get();


        return view(
            'profil.create-user',
            compact('roles', 'departements', 'fonctions')
        );

    }







    public function update(Request $request)
    {

        $user = Auth::user();



        $request->validate([

            'nom'=>'required|string|max:100',

            'prenom'=>'required|string|max:100',

            'email'=>'required|email|unique:users,email,'.$user->id,

            'photo'=>'nullable|image|max:2048',

            'signature'=>'nullable|image|mimes:png|max:2048',

            'logo'=>'nullable|image|mimes:png,jpg,jpeg|max:2048',

            'cachet'=>'nullable|image|max:2048',

            'telephone'=>'nullable|string|max:30',

            'adresse'=>'nullable|string|max:255',

            'password'=>'nullable|string|min:8|confirmed',

        ]);



        $user->nom = $request->nom;

        $user->prenom = $request->prenom;

        $user->email = $request->email;

        $user->telephone = $request->telephone;
        $user->adresse = $request->adresse;



        if($request->hasFile('photo'))
        {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $user->photo =
                $request
                ->file('photo')
                ->store('profils','public');

        }

        if ($request->hasFile('signature')) {
            if ($user->signature) {
                Storage::disk('public')->delete($user->signature);
            }

            $user->signature = $request->file('signature')->store('signatures', 'public');
        }

        if ($request->hasFile('cachet')) {
            abort_unless($user->hasRole(['Super Admin', 'Admin', 'Gérant', 'Gerant', 'Directeur Général']), 403);

            $entreprise = Entreprise::first();
            if ($entreprise) {
                if ($entreprise->cachet) {
                    Storage::disk('public')->delete($entreprise->cachet);
                }

                $entreprise->cachet = $request->file('cachet')->store('cachets', 'public');
                $entreprise->save();
            }
        }

        if ($request->hasFile('logo')) {
            abort_unless($user->hasRole(['Super Admin', 'Admin', 'Gérant', 'Gerant', 'Directeur Général']), 403);

            $entreprise = Entreprise::first();
            if ($entreprise) {
                if ($entreprise->logo) {
                    Storage::disk('public')->delete($entreprise->logo);
                }

                $entreprise->logo = $request->file('logo')->store('logos', 'public');
                $entreprise->save();
            }
        }




        if($request->filled('password'))
        {

            $user->password =
                Hash::make($request->password);


            $user->password_default = false;

        }



        $user->save();



        return back()->with(
            'success',
            'Profil modifié avec succès.'
        );

    }









    public function storeUser(Request $request)
    {


        $request->validate([


            'nom'=>'required',

            'prenom'=>'required',

            'email'=>'required|email|unique:users,email',

            'telephone'=>'required|string|max:30',

            'adresse'=>'nullable|string|max:255',

            'statut'=>'nullable|in:Actif,Inactif',

            'role_id'=>'required|exists:roles,id',

            'departement_id'=>'nullable|exists:departements,id',
            'fonction_id'=>'nullable|exists:fonctions,id',

            'photo'=>'nullable|image|max:2048',

            'signature'=>'nullable|image|mimes:png|max:2048',

        ]);

        $roleChoisi = Role::findOrFail($request->role_id);
        abort_if($roleChoisi->designation === 'Super Admin' && !Auth::user()->isSuperAdmin(), 403,
            'Seul le Super Admin peut créer un autre Super Admin.');





        // mot de passe automatique

        $password = Str::random(8);





        $user = new User();




        $user->nom = $request->nom;


        $user->prenom = $request->prenom;


        $user->email = $request->email;


        $user->telephone = $request->telephone;


        $user->adresse = $request->adresse;



        $user->role_id = $request->role_id;

        $user->departement_id = $request->departement_id;
        $user->fonction_id = $request->fonction_id;



        $user->password =
            Hash::make($password);




        // oblige changement à la première connexion

        $user->password_default = true;



        $user->statut =
            $request->statut ?? 'Actif';



        $user->email_verified_at = now();



        $user->last_logged_in = null;




        if($request->hasFile('photo'))
        {

            $user->photo =
                $request
                ->file('photo')
                ->store('profils','public');

        }

        if ($request->hasFile('signature')) {
            $user->signature = $request->file('signature')->store('signatures', 'public');
        }






        $user->save();






        return redirect()
            ->route($request->input('source') === 'parametres' ? 'parametres.utilisateurs' : 'profil.create')
            ->with([

                'success'=>'Agent créé avec succès',

                'agent_email'=>$user->email,

                'password_default'=>$password

            ]);



    }


}
