<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\EtatBesoinController;
use App\Http\Controllers\SortieCaisseController;
use App\Http\Controllers\EntreeCaisseController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\ListeDesComptesController;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

// LOGIN
Route::get('/', [AuthController::class, 'login'])->name('login');
Route::post('/', [AuthController::class, 'handlelogin'])->name('handlelogin');

// LOGIN SUCCESS
Route::get('/login/succes', [AuthController::class, 'loginSucces'])
    ->name('login.succes');

// CHANGE PASSWORD (AUTH ONLY)
Route::middleware('auth')->group(function () {
  
    // Profil
  
    // Afficher profil
    Route::get('/profil',[ProfilController::class,'index'])->name('profil.index');
    // Modifier profil
    Route::post('/profil/update',[ProfilController::class,'update'])->name('profil.update');
    // Formulaire nouvel utilisateur
    Route::get('/profil/create',[ProfilController::class,'createUser'])->name('profil.create');
    // Enregistrer nouvel utilisateur
    Route::post('/profil/store-user',[ProfilController::class,'storeUser'])->name('profil.user.store');

    Route::get('/password-change', [AuthController::class, 'showChangePasswordForm'])
        ->name('password.change');

    Route::get('/parametres',[ParametreController::class,'parametre'])->name('parametres.parametre');
    Route::get('/parametres/entreprise', [ParametreController::class, 'entreprise'])->name('parametres.entreprise');
    Route::post('/parametres/update',[ParametreController::class,'update'])->name('parametres.update');

    Route::get('/parametres/liste-des-comptes',[ParametreController::class, 'comptes'])->name('parametres.comptes');
    Route::get('/parametres/liste-des-comptes/{id}/edit',[ParametreController::class,'editCompte'])->name('parametres.comptes.edit');
    Route::put('/parametres/liste-des-comptes/{id}',[ParametreController::class,'updateCompte'])->name('parametres.comptes.update');
    Route::delete('/parametres/liste-des-comptes/{id}',[ParametreController::class,'destroyCompte'])->name('parametres.comptes.destroy');
    Route::get('/parametres/liste-des-comptes/create',[ParametreController::class,'createCompte'])->name('parametres.comptes.create');
    Route::post('/parametres/liste-des-comptes',[ParametreController::class,'storeCompte'])->name('parametres.comptes.store');


    Route::post('/change-password', [AuthController::class, 'updatePassword'])
        ->name('password.update');

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
    Route::get('/journaux/caisse', [JournalController::class, 'caisse'])->name('journaux.caisse');
    Route::get('/journaux/banque', [JournalController::class, 'banque'])->name('journaux.banque');
    Route::get('/journaux/mobile', [JournalController::class, 'mobile'])->name('journaux.mobile');
    Route::get('/journaux/tresorerie',[JournalController::class,'tresorerie'])->name('journaux.tresorerie');
    Route::resource('journaux', JournalController::class);
    // Validation / Rejet
    Route::post('/journaux/{id}/valider', [JournalController::class, 'valider'])
        ->name('journaux.valider');
    Route::post('/journaux/{id}/rejeter', [JournalController::class, 'rejeter'])
        ->name('journaux.rejeter');
});