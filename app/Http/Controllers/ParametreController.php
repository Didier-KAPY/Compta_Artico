<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TauxMc;
use App\Models\TauxDeChange;
use App\Models\ParametrageComptable;
use App\Models\Entreprise;
use App\Models\ListeDesComptes;
use Illuminate\Support\Facades\Auth;
use App\Models\JournalType;
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

    $observation = null;

    if ($request->nature == 'Actif' || $request->nature == 'Passif') {
        $observation = 'Bilan';
    }

    if ($request->nature == 'Charge' || $request->nature == 'Produit') {
        $observation = 'Gestion';
    }

    ListeDesComptes::create([
        'user_id' => Auth::id(),
        'compte' => $request->compte,
        'designation' => $request->designation,
        'nature' => $request->nature,
        'observation' => $observation,
    ]);

    return redirect()
        ->route('parametres.comptes')
        ->with(
            'success',
            'Compte créé avec succès.'
        );
}

//taux de change
public function createTauxMc()
{
    $taux = TauxMc::where('user_id', Auth::id())->first();

    return view(
        'parametres.taux_mc.create',
        compact('taux')
    );
}

public function storeTauxMc(Request $request)
{
    $request->validate([
        'taux_mc' => 'required|numeric|min:0',
    ]);

    TauxMc::updateOrCreate(
        [
            'user_id' => Auth::id(),
        ],
        [
            'taux_mc' => $request->taux_mc,
        ]
    );

    return redirect()
        ->route('parametres.taux-mc.create')
        ->with(
            'success',
            'Taux MC enregistré avec succès.'
        );
}

    /*
    |--------------------------------------------------------------------------
    | Liste des journaux
    |--------------------------------------------------------------------------
    */

    public function journalTypes()
    {

        $journalTypes = JournalType::with('compte')
            ->orderBy('code')
            ->get();


        return view(
            'parametres.journal-types.index',
            compact('journalTypes')
        );

    }



    /*
    |--------------------------------------------------------------------------
    | Formulaire création
    |--------------------------------------------------------------------------
    */

   public function createJournalType()
{

    $comptes = ListeDesComptes::orderBy('compte')
        ->get();


    $journalTypes = JournalType::with('compte')
        ->orderBy('code')
        ->get();


    return view(
        'parametres.journal-types.create',
        compact(
            'comptes',
            'journalTypes'
        )
    );

}

    /*
    |--------------------------------------------------------------------------
    | Enregistrer journal
    |--------------------------------------------------------------------------
    */

   public function storeJournalType(Request $request)
{

    $request->validate([

        'code'=>'required|string|max:20',

        'libelle'=>'required|string',

        'liste_des_comptes_id'=>'nullable|exists:liste_des_comptes,id',

        'nature'=>'required|in:caisse,banque,mobile_money,achat,vente,od',

    ]);



    JournalType::create([

        'user_id'=>Auth::id(),

        'code'=>$request->code,

        'libelle'=>$request->libelle,

        'liste_des_comptes_id'=>$request->liste_des_comptes_id,

        'nature'=>$request->nature,

        'est_tresorerie'=>$request->boolean('est_tresorerie'),

    ]);



    return redirect()

        ->route('parametres.journal-types')

        ->with(
            'success',
            'Journal créé avec succès'
        );

}






    /*
    |--------------------------------------------------------------------------
    | Modifier
    |--------------------------------------------------------------------------
    */

    public function editJournalType($id)
    {


        $journalType = JournalType::findOrFail($id);



        $comptes = ListeDesComptes::orderBy('compte')
            ->get();




        return view(
            'parametres.journal-types.edit',
            compact(
                'journalType',
                'comptes'
            )
        );


    }







    /*
    |--------------------------------------------------------------------------
    | Mise à jour
    |--------------------------------------------------------------------------
    */

    public function updateJournalType(Request $request,$id)
    {


        $journalType = JournalType::findOrFail($id);



        $request->validate([


            'code'=>'required|max:10',


            'libelle'=>'required',


            'nature'=>'required',


        ]);





        $journalType->update([


            'code'=>$request->code,


            'libelle'=>$request->libelle,


            'liste_des_comptes_id'=>$request->liste_des_comptes_id,


            'nature'=>$request->nature,


            'est_tresorerie'=>$request->has('est_tresorerie'),



        ]);





        return redirect()
            ->route('parametres.journal-types')
            ->with(
                'success',
                'Journal modifié avec succès'
            );


    }

    /*
    |--------------------------------------------------------------------------
    | Supprimer
    |--------------------------------------------------------------------------
    */

    public function destroyJournalType($id)
    {


        $journalType = JournalType::findOrFail($id);



        $journalType->delete();



        return redirect()
            ->route('parametres.journal-types')
            ->with(
                'success',
                'Journal supprimé avec succès'
            );


    }

public function index()
{
    $comptes = ListeDesComptes::orderBy('compte')->get();

    $parametrages = ParametrageComptable::with('compte')
        ->get();


    $journaux = JournalType::with('compte')
        ->where('user_id', auth()->id())
        ->orderBy('id')
        ->get();


    return view(
        'parametres.comptables',
        compact(
            'comptes',
            'parametrages',
            'journaux'
        )
    );
}




public function store(Request $request)
{  
    $request->validate([
    'code'=>'required',
    'designation'=>'required',
    'liste_des_comptes_id'=>'required'
    ]);

    ParametrageComptable::create([
    'user_id' => Auth::id(),
    'code' => $request->code,
    'designation' => $request->designation,
    'liste_des_comptes_id' => $request->liste_des_comptes_id,
    ]);

    return back()->with(
    'success',
    'Paramètre comptable ajouté avec succès'
);

}
public function destroy($id)
{


    ParametrageComptable::findOrFail($id)
    ->delete();



    return back()->with(
        'success',
        'Paramètre supprimé'
    );


    }



}