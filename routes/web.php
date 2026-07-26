<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\EtatBesoinController;
use App\Http\Controllers\SortieCaisseController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\EntreeCaisseController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\GrandLivreController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\EcritureComptableController;
use App\Http\Controllers\ListeDesComptesController;
use App\Http\Controllers\BalanceController;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

// LOGIN
Route::get('/', [AuthController::class, 'login'])->name('login');
Route::post('/', [AuthController::class, 'handlelogin'])->name('handlelogin');

// LOGIN SUCCESS
Route::get('/login/succes', [AuthController::class, 'loginSucces'])->name('login.succes');

// ===============================
// ROUTES AUTHENTIFIEES
// ===============================
Route::middleware('auth')->group(function () {

    // ===============================
    // PROFIL UTILISATEUR
    // ===============================

    // Afficher profil
    Route::get('/profil',[ProfilController::class,'index'])->name('profil.index');
    // Modifier profil
    Route::post('/profil/update',[ProfilController::class,'update'])->name('profil.update');
    // Formulaire nouvel utilisateur
    Route::get('/profil/create',[ProfilController::class,'createUser'])->name('profil.create');
    // Enregistrer nouvel utilisateur
    Route::post('/profil/store-user',[ProfilController::class,'storeUser'])->name('profil.user.store');
    // ===============================
    // MOT DE PASSE
    // ===============================
    Route::get('/password-change',[AuthController::class,'showChangePasswordForm'])->name('password.change');
    Route::post('/change-password',[AuthController::class,'updatePassword'])->name('password.update');
    // ===============================
    // PARAMETRES ENTREPRISE
    // ===============================
    // ==================================================
// PARAMETRES ENTREPRISE
// ==================================================

Route::get(
    '/parametres',
    [ParametreController::class,'parametre']
)->name('parametres.parametre');


Route::get(
    '/parametres/entreprise',
    [ParametreController::class,'entreprise']
)->name('parametres.entreprise');


Route::post(
    '/parametres/update',
    [ParametreController::class,'update']
)->name('parametres.update');

 Route::get(
        '/parametres/comptables',
        [ParametreController::class,'index']
    )
    ->name('parametres.comptables.index');



    Route::post(
        '/parametres/comptables',
        [ParametreController::class,'store']
    )
    ->name('parametres.comptables.store');



    Route::put(
        '/parametres/comptables/{id}',
        [ParametreController::class,'update']
    )
    ->name('parametres.comptables.update');



    Route::delete(
        '/parametres/comptables/{id}',
        [ParametreController::class,'destroy']
    )
    ->name('parametres.comptables.destroy');



// ==================================================
// LISTE DES COMPTES
// ==================================================

Route::get(
    '/parametres/liste-des-comptes',
    [ParametreController::class,'comptes']
)->name('parametres.comptes');


Route::get(
    '/parametres/liste-des-comptes/create',
    [ParametreController::class,'createCompte']
)->name('parametres.comptes.create');


Route::post(
    '/parametres/liste-des-comptes',
    [ParametreController::class,'storeCompte']
)->name('parametres.comptes.store');


Route::get(
    '/parametres/liste-des-comptes/{id}/edit',
    [ParametreController::class,'editCompte']
)->name('parametres.comptes.edit');


Route::put(
    '/parametres/liste-des-comptes/{id}',
    [ParametreController::class,'updateCompte']
)->name('parametres.comptes.update');


Route::delete(
    '/parametres/liste-des-comptes/{id}',
    [ParametreController::class,'destroyCompte']
)->name('parametres.comptes.destroy');





// ==================================================
// TYPES JOURNAUX (SAGE)
// ==================================================

Route::get(
    '/parametres/journal-types',
    [ParametreController::class,'journalTypes']
)->name('parametres.journal-types');


Route::get(
    '/parametres/journal-types/create',
    [ParametreController::class,'createJournalType']
)->name('parametres.journal-types.create');


Route::post(
    '/parametres/journal-types',
    [ParametreController::class,'storeJournalType']
)->name('parametres.journal-types.store');


Route::get(
    '/parametres/journal-types/{id}/edit',
    [ParametreController::class,'editJournalType']
)->name('parametres.journal-types.edit');


Route::put(
    '/parametres/journal-types/{id}',
    [ParametreController::class,'updateJournalType']
)->name('parametres.journal-types.update');


Route::delete(
    '/parametres/journal-types/{id}',
    [ParametreController::class,'destroyJournalType']
)->name('parametres.journal-types.destroy');





// ==================================================
// TAUX DE CHANGE
// ==================================================

Route::get('/parametres/taux-change/create',[ParametreController::class,'createTauxChange'])->name('parametres.taux-change.create');
Route::post('/parametres/taux-change',[ParametreController::class,'storeTauxChange'])->name('parametres.taux-change.store');
// ==================================================
// TAUX MC
// ==================================================

Route::get(
    '/parametres/taux-mc/create',
    [ParametreController::class,'createTauxMc']
)->name('parametres.taux-mc.create');


Route::post(
    '/parametres/taux-mc',
    [ParametreController::class,'storeTauxMc']
)->name('parametres.taux-mc.store');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED AREA
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'force.password.change'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [AuthController::class, 'index'])
        ->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    /*
    |--------------------------------------------------------------------------
    | USERS MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->name('register.')->group(function () {

        Route::get('/register', [RegisterController::class, 'index'])
            ->name('index');

        Route::post('/register', [RegisterController::class, 'store'])
            ->name('store');

        Route::get('/register/{user}/edit', [RegisterController::class, 'edit'])
            ->name('edit');

        Route::put('/register/{user}', [RegisterController::class, 'update'])
            ->name('update');

        Route::delete('/register/{user}', [RegisterController::class, 'destroy'])
            ->name('destroy');

        Route::patch('/register/toggle-status/{user}', [RegisterController::class, 'toggleStatus'])
            ->name('toggleStatus');
    });
    /*
|-----------------------------------
| ETAT DE BESOINS
|-----------------------------------
*/

    Route::resource('etat-besoins', EtatBesoinController::class);
    Route::post('/etat-besoins/{id}/valider', [EtatBesoinController::class, 'valider'])
    ->name('etat-besoins.valider');

    /*
|-----------------------------------
| SORTIE CAISSES
|-----------------------------------
*/
    Route::post('/sortie-caisses/{id}/valider', 
        [SortieCaisseController::class, 'valider']
    )->name('sortie-caisses.valider');

    Route::post('/sortie-caisses/{id}/rejeter', 
        [SortieCaisseController::class, 'rejeter']
    )->name('sortie-caisses.rejeter');
    Route::resource('sortie-caisses', SortieCaisseController::class);
    Route::post(
    '/sortie-caisses/{id}/attente',
    [SortieCaisseController::class,'attente']
)->name('sortie-caisses.attente');
    /*
|-----------------------------------
| ENTREE CAISSES
|-----------------------------------
*/
    Route::get('/entree-caisses/statistiques', [EntreeCaisseController::class, 'statistiques'])
    ->name('entree-caisses.statistiques');
    Route::resource('entree-caisses', EntreeCaisseController::class);
    Route::get('/entree-caisse', [EntreeCaisseController::class, 'index'])
    ->name('entree-caisse.index');
    // Validation / Rejet
    Route::post('/entree-caisses/{id}/valider', [EntreeCaisseController::class, 'valider'])
        ->name('entree-caisses.valider');
    Route::post('/entree-caisses/{id}/rejeter', [EntreeCaisseController::class, 'rejeter'])
        ->name('entree-caisses.rejeter');
    Route::get('/entree-caisse/{id}/edit', [EntreeCaisseController::class, 'edit'])->name('entree-caisse.edit');

    Route::put('/entree-caisse/{id}', [EntreeCaisseController::class, 'update'])->name('entree-caisse.update');
        /*
    ---------------------------------
| JOURNAUX
|-----------------------------------
*/
    /*
|--------------------------------------------------------------------------
| JOURNAUX
|--------------------------------------------------------------------------
*/

Route::prefix('journaux')->name('journaux.')
    ->group(function () {


        /*
        |--------------------------------------------------------------------------
        | AFFICHAGE JOURNAUX
        |--------------------------------------------------------------------------
        */


        Route::get('/journaux',
[JournalController::class,'index'])
->name('journaux.index');


        Route::get('/banque',
            [JournalController::class,'banque']
        )->name('banque');


        Route::get('/mobile',
            [JournalController::class,'mobile']
        )->name('mobile');


        Route::get('/tresorerie',
            [JournalController::class,'tresorerie']
        )->name('tresorerie');



        /*
        |--------------------------------------------------------------------------
        | RELEVE
        |--------------------------------------------------------------------------
        */


        Route::get('/releve',
            [JournalController::class,'releve']
        )->name('releve');



        /*
        |--------------------------------------------------------------------------
        | VALIDATION / REJET
        |--------------------------------------------------------------------------
        */


        Route::post('/{id}/valider',
            [JournalController::class,'valider']
        )->name('valider');


        Route::post('/{id}/rejeter',
            [JournalController::class,'rejeter']
        )->name('rejeter');



        /*
        |--------------------------------------------------------------------------
        | RECU
        |--------------------------------------------------------------------------
        */


        Route::get('/{id}/recu',
            [JournalController::class,'recu']
        )->name('recu');



        /*
        |--------------------------------------------------------------------------
        | CRUD JOURNAUX
        |--------------------------------------------------------------------------
        */


        Route::get('/',
            [JournalController::class,'index']
        )->name('index');


        Route::get('/create',
            [JournalController::class,'create']
        )->name('create');


        Route::post('/',
            [JournalController::class,'store']
        )->name('store');


        Route::get('/{journal}',
            [JournalController::class,'show']
        )->name('show');


        Route::get('/{journal}/edit',
            [JournalController::class,'edit']
        )->name('edit');


        Route::put('/{journal}',
            [JournalController::class,'update']
        )->name('update');


        Route::delete('/{journal}',
            [JournalController::class,'destroy']
        )->name('destroy');


    });

    Route::get('/grand-livre',[GrandLivreController::class, 'index'])->name('grandlivre.index');
    
    Route::get('/ecritures/brc',[EcritureComptableController::class,'formBrc'])->name('ecritures.brc');
    Route::get('/ecritures/brc/generer',[EcritureComptableController::class,'genererBrc'])->name('ecritures.brc.generer');
    Route::get('/ecritures/brc/pdf',[EcritureComptableController::class,'brcPdf'])->name('ecritures.brc.pdf');

    //ecriture
    Route::get('/ecritures', [EcritureComptableController::class, 'liste'])->name('ecritures.liste');
    Route::post('/ecritures/{id}/valider', [EcritureComptableController::class, 'valider'])->name('ecritures.valider');
    Route::get('/ecritures/{id}/modifier', [EcritureComptableController::class, 'edit'])->name('ecritures.edit');
    Route::put('/ecritures/{id}', [EcritureComptableController::class, 'update'])->name('ecritures.update');
    Route::delete('/ecritures/{id}', [EcritureComptableController::class, 'destroy'])->name('ecritures.destroy');
    // Afficher le formulaire de saisie
    Route::get(
        '/imputation-comptes',
        [EcritureComptableController::class, 'create']
    )->name('ecritures.create');


    Route::post(
        '/imputation-comptes',
        [EcritureComptableController::class, 'store']
    )->name('ecritures.store');
   
   // Balance
    Route::get('/balance', [BalanceController::class, 'index'])->name('balance.index');
   
});