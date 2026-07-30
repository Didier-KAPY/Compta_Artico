<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EcritureComptableController;
use App\Http\Controllers\EntreeCaisseController;
use App\Http\Controllers\EtatFinancierController;
use App\Http\Controllers\EtatBesoinController;
use App\Http\Controllers\GrandLivreController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\JournalControllerRecu;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SortieCaisseController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', [AuthController::class, 'login'])->name('login');
Route::post('/', [AuthController::class, 'handlelogin'])->name('handlelogin');
Route::get('/login/succes', [AuthController::class, 'loginSucces'])->name('login.succes');

Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfilController::class, 'index'])
        ->name('profil.index');
    Route::post('/profil/update', [ProfilController::class, 'update'])
        ->name('profil.update');
    Route::get('/profil/create', [ProfilController::class, 'createUser'])
        ->middleware('role:Super Admin,Admin')
        ->name('profil.create');
    Route::post('/profil/store-user', [ProfilController::class, 'storeUser'])
        ->middleware('role:Super Admin,Admin,Directeur Général,Gérant,Gerant')
        ->name('profil.user.store');

    Route::get('/password-change', [AuthController::class, 'showChangePasswordForm'])
        ->name('password.change');
    Route::post('/change-password', [AuthController::class, 'updatePassword'])
        ->name('password.update');

    Route::get('/parametres', [ParametreController::class, 'parametre'])
        ->middleware('role:Super Admin,Admin,Directeur Général,Gérant,Gerant')
        ->name('parametres.parametre');
    Route::get('/parametres/utilisateurs', [ParametreController::class, 'utilisateurs'])
        ->middleware('role:Super Admin,Admin,Directeur Général,Gérant,Gerant')
        ->name('parametres.utilisateurs');
    Route::get('/parametres/entreprise', [ParametreController::class, 'entreprise'])
        ->middleware('role:Super Admin,Admin,Directeur Général')
        ->name('parametres.entreprise');
    Route::post('/parametres/update', [ParametreController::class, 'updateEntreprise'])
        ->middleware('role:Super Admin,Admin')
        ->name('parametres.entreprise.update');

    Route::get('/parametres/departements', [ParametreController::class, 'departements'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.departements');
    Route::post('/parametres/departements', [ParametreController::class, 'storeDepartement'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.departements.store');
    Route::put('/parametres/departements/{departement}', [ParametreController::class, 'updateDepartement'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.departements.update');
    Route::delete('/parametres/departements/{departement}', [ParametreController::class, 'destroyDepartement'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.departements.destroy');
    Route::post('/parametres/fonctions', [ParametreController::class, 'storeFonction'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.fonctions.store');
    Route::put('/parametres/fonctions/{fonction}', [ParametreController::class, 'updateFonction'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.fonctions.update');
    Route::delete('/parametres/fonctions/{fonction}', [ParametreController::class, 'destroyFonction'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.fonctions.destroy');
    Route::patch('/parametres/utilisateurs/{user}/departement', [ParametreController::class, 'affecterDepartement'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.utilisateurs.departement');

    Route::get('/parametres/comptables', [ParametreController::class, 'index'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('parametres.comptables.index');
    Route::post('/parametres/comptables', [ParametreController::class, 'store'])
        ->middleware('role:Super Admin,Admin,DAF')
        ->name('parametres.comptables.store');
    Route::put('/parametres/comptables/{id}', [ParametreController::class, 'updateParametrageComptable'])
        ->middleware('role:Super Admin,Admin,DAF')
        ->name('parametres.comptables.update');
    Route::delete('/parametres/comptables/{id}', [ParametreController::class, 'destroy'])
        ->middleware('role:Super Admin,Admin,DAF')
        ->name('parametres.comptables.destroy');

    Route::get('/parametres/liste-des-comptes', [ParametreController::class, 'comptes'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('parametres.comptes');
    Route::get('/parametres/liste-des-comptes/create', [ParametreController::class, 'createCompte'])
        ->middleware('role:Super Admin,Admin,DAF,Comptable')
        ->name('parametres.comptes.create');
    Route::post('/parametres/liste-des-comptes', [ParametreController::class, 'storeCompte'])
        ->middleware('role:Super Admin,Admin,DAF,Comptable')
        ->name('parametres.comptes.store');
    Route::get('/parametres/liste-des-comptes/{id}/edit', [ParametreController::class, 'editCompte'])
        ->middleware('role:Super Admin,Admin,DAF,Comptable')
        ->name('parametres.comptes.edit');
    Route::put('/parametres/liste-des-comptes/{id}', [ParametreController::class, 'updateCompte'])
        ->middleware('role:Super Admin,Admin,DAF,Comptable')
        ->name('parametres.comptes.update');
    Route::delete('/parametres/liste-des-comptes/{id}', [ParametreController::class, 'destroyCompte'])
        ->middleware('role:Super Admin,Admin,DAF')
        ->name('parametres.comptes.destroy');

    Route::get('/parametres/journal-types', [ParametreController::class, 'journalTypes'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('parametres.journal-types');
    Route::get('/parametres/journal-types/create', [ParametreController::class, 'createJournalType'])
        ->middleware('role:Super Admin,Admin,DAF')
        ->name('parametres.journal-types.create');
    Route::post('/parametres/journal-types', [ParametreController::class, 'storeJournalType'])
        ->middleware('role:Super Admin,Admin,DAF')
        ->name('parametres.journal-types.store');
    Route::get('/parametres/journal-types/{id}/edit', [ParametreController::class, 'editJournalType'])
        ->middleware('role:Super Admin,Admin,DAF')
        ->name('parametres.journal-types.edit');
    Route::put('/parametres/journal-types/{id}', [ParametreController::class, 'updateJournalType'])
        ->middleware('role:Super Admin,Admin,DAF')
        ->name('parametres.journal-types.update');
    Route::delete('/parametres/journal-types/{id}', [ParametreController::class, 'destroyJournalType'])
        ->middleware('role:Super Admin,Admin,DAF')
        ->name('parametres.journal-types.destroy');

    Route::get('/parametres/taux-change/create', [ParametreController::class, 'createTauxChange'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
        ->name('parametres.taux-change.create');
    Route::post('/parametres/taux-change', [ParametreController::class, 'storeTauxChange'])
        ->middleware('role:Super Admin,Admin,DAF')
        ->name('parametres.taux-change.store');
});

Route::middleware(['auth', 'force.password.change'])->group(function () {
    Route::get('/exports/{rapport}/{format}', function (Request $request, string $rapport, string $format) {
        $methodes = ['ecritures', 'journaux', 'entrees', 'sorties', 'etat-besoins', 'grand-livre', 'tresorerie', 'releve', 'balance', 'bilan', 'compte-resultat'];
        abort_unless(in_array($rapport, $methodes, true), 404);
        $methode = str_replace('-', '', lcfirst(ucwords($rapport, '-')));
        return app(ReportExportController::class)->{$methode}($request, $format);
    })->whereIn('format', ['pdf', 'excel'])->name('exports.periode');
    Route::get('/dashboard', DashboardController::class)
        ->middleware('role:Super Admin,Admin,Gérant,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière,Chef de Service,Chef de Département')
        ->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::resource('etat-besoins', EtatBesoinController::class)
        ->only(['create', 'store'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général,DAF,Comptable,Chef de Service,Chef de Département');
    Route::resource('etat-besoins', EtatBesoinController::class)
        ->only(['index', 'show']);
    Route::resource('etat-besoins', EtatBesoinController::class)
        ->only(['edit', 'update', 'destroy'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Chef de Service,Chef de département');
    Route::post('/etat-besoins/{id}/valider', [EtatBesoinController::class, 'valider'])
        ->middleware('role:Super Admin,Comptable,Chef de Service,Chef de Département')
        ->name('etat-besoins.valider');
    Route::patch('/etat-besoins/{id}/reouvrir', [EtatBesoinController::class, 'reouvrir'])
        ->middleware('role:Super Admin')->name('etat-besoins.reouvrir');

    Route::resource('sortie-caisses', SortieCaisseController::class)
        ->only(['create', 'store'])
        ->middleware('role:Super Admin,Admin,Directeur Général,Caissier,Caissière,Trésorier,Trésorière');
    Route::resource('sortie-caisses', SortieCaisseController::class)
        ->only(['edit', 'update', 'destroy'])
        ->middleware('role:Super Admin,Admin,Directeur Général');
    Route::post('/sortie-caisses/{id}/valider', [SortieCaisseController::class, 'valider'])
        ->middleware('role:Super Admin,Admin,Directeur Général')
        ->name('sortie-caisses.valider');
    Route::post('/sortie-caisses/{id}/rejeter', [SortieCaisseController::class, 'rejeter'])
        ->middleware('role:Super Admin,Admin,Directeur Général')
        ->name('sortie-caisses.rejeter');
    Route::post('/sortie-caisses/{id}/attente', [SortieCaisseController::class, 'attente'])
        ->middleware('role:Super Admin,Admin,Directeur Général')
        ->name('sortie-caisses.attente');
    Route::patch('/sortie-caisses/{id}/reouvrir', [SortieCaisseController::class, 'reouvrir'])
        ->middleware('role:Super Admin')->name('sortie-caisses.reouvrir');
    Route::resource('sortie-caisses', SortieCaisseController::class)
        ->only(['index', 'show'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière');

    Route::get('/entree-caisses/statistiques', [EntreeCaisseController::class, 'statistiques'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
        ->name('entree-caisses.statistiques');
    Route::resource('entree-caisses', EntreeCaisseController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière');
    Route::get('/entree-caisse', [EntreeCaisseController::class, 'index'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
        ->name('entree-caisse.index');
    Route::post('/entree-caisses/{id}/valider', [EntreeCaisseController::class, 'valider'])
        ->middleware('role:Super Admin,Admin,DAF,Comptable')
        ->name('entree-caisses.valider');
    Route::post('/entree-caisses/{id}/rejeter', [EntreeCaisseController::class, 'rejeter'])
        ->middleware('role:Super Admin,Admin,DAF,Comptable')
        ->name('entree-caisses.rejeter');
    Route::patch('/entree-caisses/{id}/reouvrir', [EntreeCaisseController::class, 'reouvrir'])
        ->middleware('role:Super Admin')->name('entree-caisses.reouvrir');
    Route::get('/entree-caisse/{id}/edit', [EntreeCaisseController::class, 'edit'])
        ->middleware('role:Super Admin,Admin,Caissier,Caissière,Trésorier,Trésorière')
        ->name('entree-caisse.edit');
    Route::put('/entree-caisse/{id}', [EntreeCaisseController::class, 'update'])
        ->middleware('role:Super Admin,Admin,Caissier,Caissière,Trésorier,Trésorière')
        ->name('entree-caisse.update');
    Route::resource('entree-caisses', EntreeCaisseController::class)
        ->only(['index', 'show'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière');

    Route::prefix('journaux')->name('journaux.')->group(function () {
        Route::get('/banque', [JournalController::class, 'banque'])
            ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('banque');
        Route::get('/mobile', [JournalController::class, 'mobile'])
            ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('mobile');
        Route::get('/tresorerie', [JournalController::class, 'tresorerie'])
            ->middleware('role:Super Admin,Admin,Directeur Général,Gérant,Gerant,Chef de département,Chef de service,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('tresorerie');
        Route::get('/releve', [JournalController::class, 'releve'])
            ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('releve');
        Route::post('/{id}/valider', [JournalController::class, 'valider'])
            ->middleware('role:Super Admin,Caissier,Caissière,Trésorier,Trésorière')
            ->name('valider');
        Route::patch('/{id}/reouvrir', [JournalController::class, 'reouvrir'])
            ->middleware('role:Super Admin')
            ->name('reouvrir');
        Route::post('/{id}/rejeter', [JournalController::class, 'rejeter'])
            ->middleware('role:Super Admin,Admin,DAF,Comptable')
            ->name('rejeter');
        Route::get('/{id}/recu', [JournalControllerRecu::class, 'recu'])
            ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('recu');
        Route::get('/{id}/recu/pdf', [JournalControllerRecu::class, 'telecharger'])
            ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('recu.pdf');
        Route::get('/', [JournalController::class, 'index'])
            ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('index');
        Route::get('/create', [JournalController::class, 'create'])
            ->middleware('role:Super Admin,Admin,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('create');
        Route::get('/create/caisse', [JournalController::class, 'createCaisse'])
            ->middleware('role:Super Admin,Admin,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('create.caisse');
        Route::get('/create/banque', [JournalController::class, 'createBanque'])
            ->middleware('role:Super Admin,Admin,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('create.banque');
        Route::get('/create/mobile', [JournalController::class, 'createMobile'])
            ->middleware('role:Super Admin,Admin,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('create.mobile');
        Route::post('/', [JournalController::class, 'store'])
            ->middleware('role:Super Admin,Admin,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('store');
        Route::get('/{journal}', [JournalController::class, 'show'])
            ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('show');
        Route::get('/{journal}/edit', [JournalController::class, 'edit'])
            ->middleware('role:Super Admin,Admin,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('edit');
        Route::put('/{journal}', [JournalController::class, 'update'])
            ->middleware('role:Super Admin,Admin,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('update');
        Route::delete('/{journal}', [JournalController::class, 'destroy'])
            ->middleware('role:Super Admin,Admin,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('destroy');
    });

    Route::get('/grand-livre', [GrandLivreController::class, 'index'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('grandlivre.index');
    Route::get('/ecritures/brc', [EcritureComptableController::class, 'formBrc'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('ecritures.brc');
    Route::get('/ecritures/brc/generer', [EcritureComptableController::class, 'genererBrc'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('ecritures.brc.generer');
    Route::get('/ecritures/brc/pdf', [EcritureComptableController::class, 'brcPdf'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('ecritures.brc.pdf');
    Route::get('/balance', [BalanceController::class, 'index'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('balance.index');

    Route::prefix('comptabilite/etats-financiers')
        ->name('comptabilite.etats-financiers.')
        ->middleware([
            'role:Super Admin,Admin,Directeur Général,DAF,Comptable',
            'can:viewAccountingReports',
        ])
        ->group(function () {
            Route::get('/', [EtatFinancierController::class, 'index'])->name('index');
            Route::get('/bilan', [EtatFinancierController::class, 'bilan'])->name('bilan');
            Route::get('/bilan/pdf', [EtatFinancierController::class, 'bilanPdf'])->name('bilan-pdf');
            Route::get('/compte-resultat', [EtatFinancierController::class, 'compteResultat'])->name('compte-resultat');
            Route::get('/compte-resultat/pdf', [EtatFinancierController::class, 'compteResultatPdf'])->name('compte-resultat-pdf');
        });

    Route::get('/ecritures', [EcritureComptableController::class, 'liste'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('ecritures.liste');
    Route::post('/ecritures/{id}/valider', [EcritureComptableController::class, 'valider'])
        ->middleware('role:Super Admin,Comptable')
        ->name('ecritures.valider');
    Route::patch('/ecritures/{id}/reouvrir', [EcritureComptableController::class, 'reouvrir'])
        ->middleware('role:Super Admin')
        ->name('ecritures.reouvrir');
    Route::get('/ecritures/{id}/modifier', [EcritureComptableController::class, 'edit'])
        ->middleware('role:Super Admin,Comptable')
        ->name('ecritures.edit');
    Route::put('/ecritures/{id}', [EcritureComptableController::class, 'update'])
        ->middleware('role:Super Admin,Comptable')
        ->name('ecritures.update');
    Route::delete('/ecritures/{id}', [EcritureComptableController::class, 'destroy'])
        ->middleware('role:Super Admin,Comptable')
        ->name('ecritures.destroy');
    Route::get('/imputation-comptes', [EcritureComptableController::class, 'create'])
        ->middleware('role:Super Admin,Admin,Comptable')
        ->name('ecritures.create');
    Route::post('/imputation-comptes', [EcritureComptableController::class, 'store'])
        ->middleware('role:Super Admin,Admin,Comptable')
        ->name('ecritures.store');
});
