<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Role;


class ProfilController extends Controller
{


    public function index()
    {

        $user = Auth::user()
            ->load('role');


        $roles = Role::orderBy('designation')
            ->get();


        $users = User::with('role')
            ->orderBy('nom')
            ->get();



        return view('profil.index', compact(
            'user',
            'roles',
            'users'
        ));

    }





    public function createUser()
    {

        $roles = Role::orderBy('designation')
            ->get();


        return view(
            'profil.create-user',
            compact('roles')
        );

    }







    public function update(Request $request)
    {

        $user = Auth::user();



        $request->validate([

            'nom'=>'required',

            'prenom'=>'required',

            'email'=>'required|email|unique:users,email,'.$user->id,

            'role_id'=>'required|exists:roles,id',

            'photo'=>'nullable|image|max:2048',

            'password'=>'nullable|min:6',

        ]);



        $user->nom = $request->nom;

        $user->prenom = $request->prenom;

        $user->email = $request->email;

        $user->role_id = $request->role_id;



        if($request->filled('telephone'))
        {
            $user->telephone = $request->telephone;
        }


        if($request->filled('adresse'))
        {
            $user->adresse = $request->adresse;
        }



        if($request->hasFile('photo'))
        {

            $user->photo =
                $request
                ->file('photo')
                ->store('profils','public');

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

            'telephone'=>'required',

            'adresse'=>'nullable',

            'role_id'=>'required|exists:roles,id',

            'photo'=>'nullable|image|max:2048',

        ]);





        // mot de passe automatique

        $password = Str::random(8);





        $user = new User();




        $user->nom = $request->nom;


        $user->prenom = $request->prenom;


        $user->email = $request->email;


        $user->telephone = $request->telephone;


        $user->adresse = $request->adresse;



        $user->role_id = $request->role_id;



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






        $user->save();






        return redirect()
            ->route('profil.create')
            ->with([

                'success'=>'Agent créé avec succès',

                'agent_email'=>$user->email,

                'password_default'=>$password

            ]);



    }


}