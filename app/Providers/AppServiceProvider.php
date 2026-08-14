<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Entreprise;
use Illuminate\Pagination\Paginator;
use App\Models\Journaux;
use App\Models\EcritureComptable;
use App\Policies\JournalPolicy;
use App\Policies\EcritureComptablePolicy;
use App\Models\EtatBesoin;
use App\Models\EntreeCaisse;
use App\Models\SortieCaisse;
use App\Policies\EtatBesoinPolicy;
use App\Policies\EntreeCaissePolicy;
use App\Policies\SortieCaissePolicy;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Journaux::class, JournalPolicy::class);
        Gate::policy(EcritureComptable::class, EcritureComptablePolicy::class);
        Gate::policy(EtatBesoin::class, EtatBesoinPolicy::class);
        Gate::policy(EntreeCaisse::class, EntreeCaissePolicy::class);
        Gate::policy(SortieCaisse::class, SortieCaissePolicy::class);
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->isSuperAdmin() ? true : null;
        });

        Gate::define('viewAccountingReports', fn (User $user): bool => $user->hasRole(
            ['Super Admin', 'Admin', 'DAF', 'Comptable']
        ));

        Gate::define('manageUsers', fn (User $user): bool => $user->isSuperAdmin() || $user->isManagement());

        Gate::define('manageServiceCards', fn (User $user): bool => $user->isSuperAdmin()
            || $user->isManagement()
            || $user->hasRole('Directeur Technique'));

        Gate::define('manageAccountingConfiguration', fn (User $user): bool => $user->hasRole(
            ['Super Admin', 'Admin', 'DAF']
        ));

        Gate::define('validateAccountingEntries', fn (User $user): bool => $user->hasRole(['Super Admin', 'Comptable']));

        Gate::define('createEtatBesoin', fn (User $user): bool => $user->hasRole([
            'Super Admin', 'DAF', 'Comptable', 'Chef de Service', 'Chef de Département', 'Directeur Technique',
        ]));

        Gate::define('manageEtatBesoin', fn (User $user): bool => $user->hasRole([
            'Super Admin', 'DAF', 'Comptable',
        ]));

        Gate::define('deleteEtatBesoin', fn (User $user): bool => $user->isSuperAdmin() || $user->isManagement());
        Gate::define('deleteFinancialDocument', fn (User $user): bool => $user->isSuperAdmin() || $user->isManagement());
        Gate::define('restoreFinancialDocument', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('forceDeleteFinancialDocument', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('viewFinancialTrash', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('viewFinancialAudit', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('viewBudget', fn (User $user): bool => $user->hasRole(['Super Admin','Admin','Directeur Général','Gérant','Gerant','DAF','Comptable']));
        Gate::define('createBudget', fn (User $user): bool => $user->hasRole(['Super Admin','DAF']));
        Gate::define('updateBudget', fn (User $user): bool => $user->hasRole(['Super Admin','DAF']));
        Gate::define('validateBudget', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('manageBudgetLines', fn (User $user): bool => $user->hasRole(['Super Admin','DAF']));
        Gate::define('viewBudgetExecution', fn (User $user): bool => $user->hasRole(['Super Admin','Admin','Directeur Général','Gérant','Gerant','DAF','Comptable']));
        Gate::define('reviseBudget', fn (User $user): bool => $user->hasRole(['Super Admin','DAF']));
        Gate::define('transferBudget', fn (User $user): bool => $user->hasRole(['Super Admin','DAF']));
        Gate::define('closeBudget', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('reopenBudget', fn (User $user): bool => $user->isSuperAdmin());

        Gate::define('viewEtatBesoinDetail', fn (User $user): bool => ! $user->hasRole([
            'Chef de Service', 'Chef de Département', 'Directeur Technique',
        ]));

        Gate::define('manageEntreeCaisse', fn (User $user): bool => $user->hasRole([
            'Super Admin', 'DAF', 'Comptable', 'Caissier', 'Caissière', 'Trésorier', 'Trésorière',
        ]));

        Gate::define('manageJournaux', fn (User $user): bool => $user->hasRole([
            'Super Admin', 'DAF', 'Comptable', 'Caissier', 'Caissière', 'Trésorier', 'Trésorière',
        ]));

        Gate::define('viewJournalIndex', fn (User $user): bool => $user->hasRole([
            'Super Admin', 'DAF', 'Comptable', 'Caissier', 'Caissière', 'Trésorier', 'Trésorière',
            'Directeur Technique',
        ]));

        Gate::define('viewTreasurySituation', fn (User $user): bool => $user->hasRole([
            'Super Admin', 'Admin', 'Gérant', 'Gerant', 'Directeur Général',
            'DAF', 'Comptable', 'Trésorier', 'Trésorière',
        ]));

        Paginator::useBootstrap();

        View::composer('*', function ($view) {

            $user = Auth::user();

            // Entreprise récupérée séparément
            $entreprise = Entreprise::first();

            $view->with([
                'user'       => $user,
                'entreprise' => $entreprise,
            ]);
        });
    }
}
