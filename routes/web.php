<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\BRCController;
use App\Http\Controllers\CarteServiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EcritureComptableController;
use App\Http\Controllers\EntreeCaisseController;
use App\Http\Controllers\EtatFinancierController;
use App\Http\Controllers\EtatBesoinController;
use App\Http\Controllers\GrandLivreController;
use App\Http\Controllers\FinancialTrashController;
use App\Http\Controllers\FinancialAuditController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\JournalControllerRecu;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SortieCaisseController;
use App\Http\Controllers\ClotureJournaliereController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', [AuthController::class, 'login'])->name('login');
Route::post('/', [AuthController::class, 'handlelogin'])->name('handlelogin');
Route::get('/login/succes', [AuthController::class, 'loginSucces'])->name('login.succes');

Route::middleware(['auth', 'force.password.change'])->group(function () {
    Route::get('/profil', [ProfilController::class, 'index'])
        ->name('profil.index');
    Route::post('/profil/update', [ProfilController::class, 'update'])
        ->name('profil.update');
    Route::get('/profil/create', [ProfilController::class, 'createUser'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('profil.create');
    Route::post('/profil/store-user', [ProfilController::class, 'storeUser'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('profil.user.store');

    Route::get('/password-change', [AuthController::class, 'showChangePasswordForm'])
        ->withoutMiddleware('force.password.change')
        ->name('password.change');
    Route::post('/change-password', [AuthController::class, 'updatePassword'])
        ->withoutMiddleware('force.password.change')
        ->name('password.update');

    Route::get('/parametres', [ParametreController::class, 'parametre'])
        ->middleware('role:Super Admin,Admin,Directeur Général,Gérant,Gerant,DAF,Comptable,Directeur Technique')
        ->name('parametres.parametre');
    Route::get('/parametres/audit-comptable', [FinancialAuditController::class, 'index'])
        ->middleware('can:viewFinancialAudit')
        ->name('parametres.audit.index');
    Route::prefix('/parametres/clotures-journalieres')->name('parametres.clotures.')
        ->middleware('role:Super Admin')->group(function () {
            Route::get('/', [ClotureJournaliereController::class, 'index'])->name('index');
            Route::get('/simulation', [ClotureJournaliereController::class, 'simulation'])->name('simulation');
            Route::post('/', [ClotureJournaliereController::class, 'store'])->name('store');
            Route::get('/{cloture}', [ClotureJournaliereController::class, 'show'])->name('show');
            Route::post('/{cloture}/verifier', [ClotureJournaliereController::class, 'verifier'])->name('verifier');
            Route::post('/{cloture}/reouvrir', [ClotureJournaliereController::class, 'reouvrir'])->name('reouvrir');
        });

    Route::prefix('/parametres/cartes-service')
        ->name('parametres.cartes-service.')
        ->middleware('can:manageServiceCards')
        ->group(function () {
            Route::get('/', [CarteServiceController::class, 'index'])->name('index');
            Route::get('/create', [CarteServiceController::class, 'create'])->name('create');
            Route::post('/', [CarteServiceController::class, 'store'])->name('store');
            Route::get('/{carteService}', [CarteServiceController::class, 'show'])->name('show');
            Route::get('/{carteService}/edit', [CarteServiceController::class, 'edit'])->name('edit');
            Route::put('/{carteService}', [CarteServiceController::class, 'update'])->name('update');
            Route::delete('/{carteService}', [CarteServiceController::class, 'destroy'])->name('destroy');
            Route::get('/{carteService}/pdf', [CarteServiceController::class, 'pdf'])->name('pdf');
        });
    Route::get('/parametres/utilisateurs', [ParametreController::class, 'utilisateurs'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.utilisateurs');
    Route::get('/parametres/entreprise', [ParametreController::class, 'entreprise'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.entreprise');
    Route::post('/parametres/update', [ParametreController::class, 'updateEntreprise'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
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
    Route::patch('/parametres/utilisateurs/{user}/role', [ParametreController::class, 'changerRole'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.utilisateurs.role');
    Route::patch('/parametres/utilisateurs/{user}/mot-de-passe', [ParametreController::class, 'reinitialiserMotDePasse'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général')
        ->name('parametres.utilisateurs.password.reset');

    Route::get('/parametres/comptables', [ParametreController::class, 'index'])
        ->middleware('role:Super Admin,DAF,Comptable')
        ->name('parametres.comptables.index');
    Route::post('/parametres/comptables', [ParametreController::class, 'store'])
        ->middleware('role:Super Admin,DAF')
        ->name('parametres.comptables.store');
    Route::put('/parametres/comptables/{id}', [ParametreController::class, 'updateParametrageComptable'])
        ->middleware('role:Super Admin,DAF')
        ->name('parametres.comptables.update');
    Route::delete('/parametres/comptables/{id}', [ParametreController::class, 'destroy'])
        ->middleware('role:Super Admin,DAF')
        ->name('parametres.comptables.destroy');

    Route::get('/parametres/liste-des-comptes', [ParametreController::class, 'comptes'])
        ->middleware('role:Super Admin,DAF,Comptable')
        ->name('parametres.comptes');
    Route::get('/parametres/liste-des-comptes/create', [ParametreController::class, 'createCompte'])
        ->middleware('role:Super Admin,DAF,Comptable')
        ->name('parametres.comptes.create');
    Route::post('/parametres/liste-des-comptes', [ParametreController::class, 'storeCompte'])
        ->middleware('role:Super Admin,DAF,Comptable')
        ->name('parametres.comptes.store');
    Route::get('/parametres/liste-des-comptes/{id}/edit', [ParametreController::class, 'editCompte'])
        ->middleware('role:Super Admin,DAF,Comptable')
        ->name('parametres.comptes.edit');
    Route::put('/parametres/liste-des-comptes/{id}', [ParametreController::class, 'updateCompte'])
        ->middleware('role:Super Admin,DAF,Comptable')
        ->name('parametres.comptes.update');
    Route::delete('/parametres/liste-des-comptes/{id}', [ParametreController::class, 'destroyCompte'])
        ->middleware('role:Super Admin,DAF')
        ->name('parametres.comptes.destroy');

    Route::get('/parametres/journal-types', [ParametreController::class, 'journalTypes'])
        ->middleware('role:Super Admin,DAF,Comptable')
        ->name('parametres.journal-types');
    Route::get('/parametres/journal-types/create', [ParametreController::class, 'createJournalType'])
        ->middleware('role:Super Admin,DAF')
        ->name('parametres.journal-types.create');
    Route::post('/parametres/journal-types', [ParametreController::class, 'storeJournalType'])
        ->middleware('role:Super Admin,DAF')
        ->name('parametres.journal-types.store');
    Route::get('/parametres/journal-types/{id}/edit', [ParametreController::class, 'editJournalType'])
        ->middleware('role:Super Admin,DAF')
        ->name('parametres.journal-types.edit');
    Route::put('/parametres/journal-types/{id}', [ParametreController::class, 'updateJournalType'])
        ->middleware('role:Super Admin,DAF')
        ->name('parametres.journal-types.update');
    Route::delete('/parametres/journal-types/{id}', [ParametreController::class, 'destroyJournalType'])
        ->middleware('role:Super Admin,DAF')
        ->name('parametres.journal-types.destroy');

    Route::get('/parametres/taux-change/create', [ParametreController::class, 'createTauxChange'])
        ->middleware('role:Super Admin,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
        ->name('parametres.taux-change.create');
    Route::post('/parametres/taux-change', [ParametreController::class, 'storeTauxChange'])
        ->middleware('role:Super Admin,DAF')
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
        ->middleware('role:Super Admin,Admin,Gérant,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière,Directeur Technique')
        ->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])
        ->withoutMiddleware('force.password.change')
        ->name('logout');

    Route::prefix('corbeille')->name('corbeille.')->middleware('can:viewFinancialTrash')->group(function () {
        Route::get('/', [FinancialTrashController::class, 'index'])->name('index');
        Route::get('/{module}/{id}', [FinancialTrashController::class, 'show'])->name('show');
        Route::post('/{module}/{id}/restaurer', [FinancialTrashController::class, 'restore'])->name('restore');
        Route::delete('/{module}/{id}/definitivement', [FinancialTrashController::class, 'forceDelete'])->name('force-delete');
    });

    Route::get('/etat-besoins/{id}/imprimer', [EtatBesoinController::class, 'imprimer'])
        ->middleware('can:viewEtatBesoinDetail')
        ->name('etat-besoins.imprimer');
    Route::get('/etat-besoins/{id}/pdf', [EtatBesoinController::class, 'telechargerPdf'])
        ->middleware('can:viewEtatBesoinDetail')
        ->name('etat-besoins.pdf');
    Route::resource('etat-besoins', EtatBesoinController::class)
        ->only(['create', 'store'])
        ->middleware('can:createEtatBesoin');
    Route::resource('etat-besoins', EtatBesoinController::class)
        ->only(['index']);
    Route::resource('etat-besoins', EtatBesoinController::class)
        ->only(['show'])
        ->middleware('can:viewEtatBesoinDetail');
    Route::resource('etat-besoins', EtatBesoinController::class)
        ->only(['edit', 'update'])
        ->middleware('can:manageEtatBesoin');
    Route::delete('/etat-besoins/{etat_besoin}', [EtatBesoinController::class, 'destroy'])
        ->middleware('can:deleteFinancialDocument')
        ->name('etat-besoins.destroy');
    Route::post('/etat-besoins/{id}/valider', [EtatBesoinController::class, 'valider'])
        ->middleware('role:Super Admin,Admin,Gérant,Gerant,Directeur Général,Comptable')
        ->name('etat-besoins.valider');
    Route::patch('/etat-besoins/{id}/reouvrir', [EtatBesoinController::class, 'reouvrir'])
        ->middleware('role:Super Admin')->name('etat-besoins.reouvrir');

    Route::get('/sortie-caisses/{id}/imprimer', [SortieCaisseController::class, 'imprimer'])
        ->name('sortie-caisses.imprimer');
    Route::get('/sortie-caisses/{id}/pdf', [SortieCaisseController::class, 'telechargerPdf'])
        ->name('sortie-caisses.pdf');
    Route::resource('sortie-caisses', SortieCaisseController::class)
        ->only(['create', 'store'])
        ->middleware('role:Super Admin,Admin,Directeur Général,Caissier,Caissière,Trésorier,Trésorière');
    Route::resource('sortie-caisses', SortieCaisseController::class)
        ->only(['edit', 'update'])
        ->middleware('role:Super Admin,Admin,Directeur Général');
    Route::delete('/sortie-caisses/{sortie_caiss}', [SortieCaisseController::class, 'destroy'])
        ->middleware('can:deleteFinancialDocument')->name('sortie-caisses.destroy');
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
        ->middleware('role:Super Admin,Admin')->name('sortie-caisses.reouvrir');
    Route::resource('sortie-caisses', SortieCaisseController::class)
        ->only(['index', 'show'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière');

    Route::get('/entree-caisses/statistiques', [EntreeCaisseController::class, 'statistiques'])
        ->middleware('can:manageEntreeCaisse')
        ->name('entree-caisses.statistiques');
    Route::get('/entree-caisses/{id}/imprimer', [EntreeCaisseController::class, 'imprimer'])
        ->name('entree-caisses.imprimer');
    Route::get('/entree-caisses/{id}/pdf', [EntreeCaisseController::class, 'telechargerPdf'])
        ->name('entree-caisses.pdf');
    Route::resource('entree-caisses', EntreeCaisseController::class)
        ->only(['create', 'store', 'edit', 'update'])
        ->middleware('can:manageEntreeCaisse');
    Route::delete('/entree-caisses/{entree_caiss}', [EntreeCaisseController::class, 'destroy'])
        ->middleware('can:deleteFinancialDocument')->name('entree-caisses.destroy');
    Route::get('/entree-caisse', [EntreeCaisseController::class, 'index'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
        ->name('entree-caisse.index');
    Route::post('/entree-caisses/{id}/valider', [EntreeCaisseController::class, 'valider'])
        ->middleware('can:manageEntreeCaisse')
        ->name('entree-caisses.valider');
    Route::post('/entree-caisses/{id}/rejeter', [EntreeCaisseController::class, 'rejeter'])
        ->middleware('can:manageEntreeCaisse')
        ->name('entree-caisses.rejeter');
    Route::patch('/entree-caisses/{id}/reouvrir', [EntreeCaisseController::class, 'reouvrir'])
        ->middleware('role:Super Admin')->name('entree-caisses.reouvrir');
    Route::get('/entree-caisse/{id}/edit', [EntreeCaisseController::class, 'edit'])
        ->middleware('can:manageEntreeCaisse')
        ->name('entree-caisse.edit');
    Route::put('/entree-caisse/{id}', [EntreeCaisseController::class, 'update'])
        ->middleware('can:manageEntreeCaisse')
        ->name('entree-caisse.update');
    Route::resource('entree-caisses', EntreeCaisseController::class)
        ->only(['index', 'show'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière');

    Route::prefix('journaux')->name('journaux.')->group(function () {
        Route::get('/banque', [JournalController::class, 'banque'])
            ->middleware('can:manageJournaux')
            ->name('banque');
        Route::get('/mobile', [JournalController::class, 'mobile'])
            ->middleware('can:manageJournaux')
            ->name('mobile');
        Route::get('/tresorerie', [JournalController::class, 'tresorerie'])
            ->middleware('can:viewTreasurySituation')
            ->name('tresorerie');
        Route::get('/releve', [JournalController::class, 'releve'])
            ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('releve');
        Route::post('/{id}/valider', [JournalController::class, 'valider'])
            ->middleware('role:Super Admin,Comptable,Caissier,Caissière,Trésorier,Trésorière')
            ->name('valider');
        Route::patch('/{id}/reouvrir', [JournalController::class, 'reouvrir'])
            ->middleware('role:Super Admin')
            ->name('reouvrir');
        Route::post('/{id}/rejeter', [JournalController::class, 'rejeter'])
            ->middleware('role:Super Admin')
            ->name('rejeter');
        Route::get('/{id}/recu', [JournalControllerRecu::class, 'recu'])
            ->middleware('can:manageJournaux')
            ->name('recu');
        Route::get('/{id}/recu/pdf', [JournalControllerRecu::class, 'telecharger'])
            ->middleware('can:manageJournaux')
            ->name('recu.pdf');
        Route::get('/{journal}/piece-justificative', [JournalController::class, 'pieceJustificative'])
            ->middleware('can:manageJournaux')
            ->name('piece');
        Route::get('/', [JournalController::class, 'index'])
            ->middleware('can:viewJournalIndex')
            ->name('index');
        Route::get('/create', [JournalController::class, 'create'])
            ->middleware('can:manageJournaux')
            ->name('create');
        Route::get('/create/caisse', [JournalController::class, 'createCaisse'])
            ->middleware('can:manageJournaux')
            ->name('create.caisse');
        Route::get('/create/banque', [JournalController::class, 'createBanque'])
            ->middleware('can:manageJournaux')
            ->name('create.banque');
        Route::get('/create/mobile', [JournalController::class, 'createMobile'])
            ->middleware('can:manageJournaux')
            ->name('create.mobile');
        Route::post('/', [JournalController::class, 'store'])
            ->middleware('can:manageJournaux')
            ->name('store');
        Route::get('/{journal}', [JournalController::class, 'show'])
            ->middleware('can:manageJournaux')
            ->name('show');
        Route::get('/{journal}/edit', [JournalController::class, 'edit'])
            ->middleware('can:manageJournaux')
            ->name('edit');
        Route::put('/{journal}', [JournalController::class, 'update'])
            ->middleware('can:manageJournaux')
            ->name('update');
        Route::delete('/{journal}', [JournalController::class, 'destroy'])
            ->middleware('can:deleteFinancialDocument')
            ->name('destroy');
    });

    Route::get('/grand-livre', [GrandLivreController::class, 'index'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('grandlivre.index');
    Route::get('/brc', [BRCController::class, 'index'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('brc.index');
    Route::get('/brc/create', [BRCController::class, 'create'])
        ->middleware('role:Super Admin,Admin,Comptable')
        ->name('brc.create');
    Route::get('/brc/{brc}/pdf', [BRCController::class, 'telechargerPdf'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('brc.pdf');
    Route::get('/brc/{brc}/excel', [BRCController::class, 'telechargerExcel'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('brc.excel');
    Route::get('/brc/{brc}', [BRCController::class, 'show'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('brc.show');
    Route::post('/brc', [BRCController::class, 'store'])
        ->middleware('role:Super Admin,Admin,Comptable')
        ->name('brc.store');
    Route::post('/brc/{brc}/valider', [BRCController::class, 'valider'])
        ->middleware('role:Super Admin,Comptable')
        ->name('brc.valider');
    Route::delete('/brc/{brc}', [BRCController::class, 'destroy'])
        ->middleware('can:deleteFinancialDocument')->name('brc.destroy');
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
    Route::get('/ecritures/{id}', [EcritureComptableController::class, 'show'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('ecritures.show');
    Route::post('/ecritures/{id}/valider', [EcritureComptableController::class, 'valider'])
        ->middleware('role:Super Admin,Comptable')
        ->name('ecritures.valider');
    Route::get('/ecritures/{id}/piece-justificative', [EcritureComptableController::class, 'pieceJustificative'])
        ->middleware('role:Super Admin,Admin,Directeur Général,DAF,Comptable')
        ->name('ecritures.piece');
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
        ->middleware('can:deleteFinancialDocument')
        ->name('ecritures.destroy');
    Route::get('/imputation-comptes', [EcritureComptableController::class, 'create'])
        ->middleware('role:Super Admin,Admin,Comptable,Caissier,Caissière,Trésorier,Trésorière')
        ->name('ecritures.create');
    Route::post('/imputation-comptes', [EcritureComptableController::class, 'store'])
        ->middleware('role:Super Admin,Admin,Comptable,Caissier,Caissière,Trésorier,Trésorière')
        ->name('ecritures.store');
});
