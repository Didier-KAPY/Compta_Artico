<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Entreprise;
use App\Models\ListeDesComptes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ParametreController extends Controller
{

    public function parametre()
    {

        $entreprise = Entreprise::first();

        return view(
            'parametres.parametre',
            compact('entreprise')
        );

    }
    public function entreprise()
    {
        $entreprise = Entreprise::first();

        return view(
            'parametres.entreprise.index',
            compact('entreprise')
        );
    }
    public function update(Request $request)
    {

        $request->validate([

            'nom_entreprise' => 'required',
            'adresse' => 'required',
            'telephone' => 'required',
            'forme_juridique' => 'required',
            'numero_identification_fiscal' => 'required',
            'logo' => 'nullable|image|max:2048',
        ]);
        $entreprise = Entreprise::first();
        if(!$entreprise){
            $entreprise = new Entreprise();
            // utilisateur qui crée l'entreprise
            $entreprise->user_id = Auth::id();
        }
        $entreprise->nom_entreprise = $request->nom_entreprise;
        $entreprise->adresse = $request->adresse;
        $entreprise->telephone = $request->telephone;
        $entreprise->forme_juridique = $request->forme_juridique;
        $entreprise->numero_identification_fiscal = $request->numero_identification_fiscal;

        // LOGO

        if($request->hasFile('logo')){
            if($entreprise->logo){
                Storage::disk('public')
                ->delete($entreprise->logo);
            }
            $entreprise->logo =
            $request->file('logo')
            ->store('logos','public');
        }
        // sécurité : associer aussi l'utilisateur connecté
        $entreprise->user_id = Auth::id();
        $entreprise->save();
        return back()->with(
            'success',
            'Informations entreprise mises à jour.'
        );


    }
    
   public function comptes()
{
    $comptes = ListeDesComptes::with('user')
        ->orderBy('compte')
        ->Paginate(15);

    return view(
        'parametres.liste_des_comptes.comptes',
        compact('comptes')
    );
}

public function editCompte($id)
{
    $compte = ListeDesComptes::findOrFail($id);
    return view(
        'parametres.liste_des_comptes.edit',
        compact('compte')
    );
}

public function updateCompte(Request $request, $id)
{
    $request->validate([
        'compte'=>'required',
        'designation'=>'required',
        'nature'=>'required',

    ]);
    $compte = ListeDesComptes::findOrFail($id);
    $compte->compte = $request->compte;
    $compte->designation = $request->designation;
    $compte->nature = $request->nature;
    $compte->observation = $request->observation;

    $compte->save();
    return redirect()
        ->route('parametres.comptes')
        ->with(
            'success',
            'Compte modifié avec succès.'
        );
}
public function destroyCompte($id)
{
    $compte = ListeDesComptes::findOrFail($id);
    $compte->delete();
    return redirect()
        ->route('parametres.comptes')
        ->with(
            'success',
            'Compte supprimé avec succès.'
        );
}
public function createCompte()
{
    return view(
        'parametres.liste_des_comptes.create'
    );
}
public function storeCompte(Request $request)
{
    $request->validate([
        'compte' => 'required',
        'designation' => 'required',
        'nature' => 'required',
    ]);

    ListeDesComptes::create([
        'user_id' => Auth::id(),
        'compte' => $request->compte,
        'designation' => $request->designation,
        'nature' => $request->nature,
        'observation' => $request->observation,
    ]);
    return redirect()
        ->route('parametres.comptes')
        ->with(
            'success',
            'Compte créé avec succès.'
        );
}
}