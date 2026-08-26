<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TauxDeChange;
use App\Models\ParametrageComptable;
use App\Models\Entreprise;
use App\Models\ListeDesComptes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\JournalType;
use App\Models\Departement;
use App\Models\Fonction;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ParametreController extends Controller
{
    public function utilisateurs()
    {
        $roles = Role::orderBy('designation')->get();
        $departements = Departement::orderBy('designation')->get();
        $fonctions = Fonction::orderBy('designation')->get();
        $users = User::with(['role', 'departement', 'fonction'])
            ->orderByDesc('last_logged_in')->orderBy('nom')->paginate(15);

        return view('parametres.utilisateurs.index', compact('roles', 'departements', 'fonctions', 'users'));
    }

    public function departements()
    {
        $departements = Departement::withCount(['users', 'etatBesoins'])->orderBy('designation')->paginate(10, ['*'], 'departements_page');
        $fonctions = Fonction::withCount('users')->orderBy('designation')->paginate(10, ['*'], 'fonctions_page');
        $users = User::with(['role', 'departement', 'fonction'])->orderBy('nom')->orderBy('prenom')->paginate(10, ['*'], 'users_page');
        $tousDepartements = Departement::orderBy('designation')->get();
        $toutesFonctions = Fonction::orderBy('designation')->get();
        return view('parametres.departements.index', compact('departements', 'fonctions', 'users', 'tousDepartements', 'toutesFonctions'));
    }

    public function storeDepartement(Request $request)
    {
        $data = $request->validate([
            'designation' => 'required|string|max:150|unique:departements,designation',
        ]);
        Departement::create($data);
        return back()->with('success', 'Direction ajoutée avec succès.');
    }

    public function updateDepartement(Request $request, Departement $departement)
    {
        $data = $request->validate([
            'designation' => 'required|string|max:150|unique:departements,designation,'.$departement->id,
        ]);
        $departement->update($data);
        return back()->with('success', 'Direction mise à jour.');
    }

    public function storeFonction(Request $request)
    {
        $data = $request->validate([
            'designation' => 'required|string|max:150|unique:fonctions,designation',
        ]);
        Fonction::create($data);
        return back()->with('success', 'Fonction ajoutée avec succès.');
    }

    public function updateFonction(Request $request, Fonction $fonction)
    {
        $data = $request->validate([
            'designation' => 'required|string|max:150|unique:fonctions,designation,'.$fonction->id,
        ]);
        $fonction->update($data);
        return back()->with('success', 'Fonction mise à jour.');
    }

    public function destroyFonction(Fonction $fonction)
    {
        if ($fonction->users()->exists()) {
            return back()->with('error', 'Cette fonction est affectée à un utilisateur et ne peut pas être supprimée.');
        }
        $fonction->delete();
        return back()->with('success', 'Fonction supprimée.');
    }

    public function destroyDepartement(Departement $departement)
    {
        if ($departement->users()->exists() || $departement->etatBesoins()->exists()) {
            return back()->with('error', 'Cette direction est utilisée et ne peut pas être supprimée.');
        }
        $departement->delete();
        return back()->with('success', 'Direction supprimée.');
    }

    public function affecterDepartement(Request $request, User $user)
    {
        $data = $request->validate([
            'departement_id' => 'nullable|exists:departements,id',
            'fonction_id' => 'nullable|exists:fonctions,id',
        ]);
        $user->update($data);
        return back()->with('success', 'Affectation de l’utilisateur mise à jour.');
    }

    public function changerRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($data['role_id']);
        $acteur = $request->user();

        abort_if(($user->isSuperAdmin() || $role->designation === 'Super Admin') && ! $acteur->isSuperAdmin(), 403,
            'Seul le Super Admin peut modifier ou attribuer le rôle Super Admin.');

        $user->update(['role_id' => $role->id]);

        return back()->with('success', 'Le rôle de l’utilisateur a été mis à jour.');
    }

    public function reinitialiserMotDePasse(Request $request, User $user)
    {
        abort_if($user->isSuperAdmin() && ! $request->user()->isSuperAdmin(), 403,
            'Seul le Super Admin peut réinitialiser le mot de passe d’un Super Admin.');

        $password = Str::password(12);

        $user->forceFill([
            'password' => Hash::make($password),
            'password_default' => true,
            'remember_token' => null,
        ])->save();

        return back()->with([
            'success' => 'Le mot de passe a été réinitialisé.',
            'agent_email' => $user->email,
            'password_default' => $password,
        ]);
    }

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
    public function updateEntreprise(Request $request)
    {
        $request->validate([
            'nom_entreprise' => 'required',
            'slogan' => 'nullable|string|max:255',
            'adresse' => 'required',
            'telephone' => 'required',
            'forme_juridique' => 'required',
            'numero_identification_fiscal' => 'required',
            'logo' => 'nullable|image|max:2048',
            'cachet' => 'nullable|image|max:2048',
        ]);
        $entreprise = Entreprise::first();
        if(!$entreprise){
            $entreprise = new Entreprise();
            // utilisateur qui crée l'entreprise
            $entreprise->user_id = Auth::id();
        }
        $entreprise->nom_entreprise = $request->nom_entreprise;
        $entreprise->slogan = $request->slogan;
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
        if ($request->hasFile('cachet')) {
            if ($entreprise->cachet) {
                Storage::disk('public')->delete($entreprise->cachet);
            }
            $entreprise->cachet = $request->file('cachet')->store('cachets', 'public');
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
    $request->merge(['compte' => trim((string) $request->input('compte'))]);
    $request->validate([
        'compte' => ['required', 'string', 'max:20', Rule::unique('liste_des_comptes', 'compte')->ignore($id)],
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
    $request->merge(['compte' => trim((string) $request->input('compte'))]);
    $validated = $request->validate([
        'compte' => ['required', 'string', 'max:20', 'unique:liste_des_comptes,compte'],
        'designation' => 'required|string|max:255',
        'nature' => 'required|in:Actif,Passif,Charge,Produit',
    ]);

    $observation = null;

    if ($request->nature == 'Actif' || $request->nature == 'Passif') {
        $observation = 'Bilan';
    }

    if ($request->nature == 'Charge' || $request->nature == 'Produit') {
        $observation = 'Gestion';
    }

    DB::transaction(function () use ($validated, $observation): void {
        $compte = ListeDesComptes::create([
            'user_id' => Auth::id(),
            'compte' => $validated['compte'],
            'designation' => $validated['designation'],
            'nature' => $validated['nature'],
            'observation' => $observation,
        ]);

        JournalType::create([
            'user_id' => Auth::id(),
            'code' => Str::of($compte->designation)
                ->ascii()
                ->upper()
                ->replaceMatches('/[^A-Z0-9]/', '')
                ->substr(0, 3)
                ->toString(),
            'libelle' => $compte->designation,
            'liste_des_comptes_id' => $compte->id,
            'nature' => 'od',
            'monnaie' => 'CDF',
            'est_tresorerie' => false,
        ]);
    });

    return redirect()
        ->route('parametres.comptes')
        ->with(
            'success',
            'Compte créé avec succès.'
        );
}

public function createTauxChange()
{
    $taux = TauxDeChange::latest()
        ->first();

    return view(
        'parametres.taux_change.create',
        compact('taux')
    );
}

public function storeTauxChange(Request $request)
{
    $request->validate([
        'taux_de_change' => 'required|numeric|gt:0',
    ]);

    TauxDeChange::create([
        'user_id' => Auth::id(),
        'entreprise_id' => Entreprise::first()?->id,
        'devise_source' => 'USD',
        'devise_cible' => 'CDF',
        'taux_de_change' => $request->taux_de_change,
        'date_taux' => today(),
    ]);

    return back()->with(
        'success',
        'Taux de change enregistré avec succès.'
    );
}

    /*
    |--------------------------------------------------------------------------
    | Liste des journaux
    |--------------------------------------------------------------------------
    */

    public function journalTypes()
    {

        $journalTypes = JournalType::with(['compte', 'user'])
            ->orderBy('code')
            ->paginate(15);


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
        ->paginate(15);


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

        'monnaie'=>'required|in:CDF,USD',

    ]);



    JournalType::create([

        'user_id'=>Auth::id(),

        'code'=>$request->code,

        'libelle'=>$request->libelle,

        'liste_des_comptes_id'=>$request->liste_des_comptes_id,

        'nature'=>$request->nature,

        'monnaie'=>$request->monnaie,

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

            'monnaie'=>'required|in:CDF,USD',


        ]);





        $journalType->update([


            'code'=>$request->code,


            'libelle'=>$request->libelle,


            'liste_des_comptes_id'=>$request->liste_des_comptes_id,


            'nature'=>$request->nature,

            'monnaie'=>$request->monnaie,


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

    $parametrages = ParametrageComptable::with(['compte', 'user'])
        ->paginate(15);


    $journaux = JournalType::with('compte')
        ->orderBy('id')
        ->get();


    return view(
        'parametres.ParametrageComptable.index',
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

public function updateParametrageComptable(Request $request, $id)
{
    $request->validate([
        'code' => 'required',
        'designation' => 'required',
        'liste_des_comptes_id' => 'required|exists:liste_des_comptes,id',
    ]);

    $parametrage = ParametrageComptable::findOrFail($id);

    $parametrage->update([
        'code' => $request->code,
        'designation' => $request->designation,
        'liste_des_comptes_id' => $request->liste_des_comptes_id,
    ]);

    return back()->with(
        'success',
        'Paramètre comptable modifié avec succès'
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
