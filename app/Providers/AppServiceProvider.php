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

        Gate::define('viewAccountingReports', fn (User $user): bool => in_array(
            $user->role?->designation,
            ['Super Admin', 'Admin', 'Directeur Général', 'DAF', 'Comptable'],
            true
        ));

        Gate::define('manageUsers', fn (User $user): bool => in_array(
            $user->role?->designation,
            ['Super Admin', 'Admin'],
            true
        ));

        Gate::define('manageAccountingConfiguration', fn (User $user): bool => in_array(
            $user->role?->designation,
            ['Super Admin', 'Admin', 'DAF'],
            true
        ));

        Gate::define('validateAccountingEntries', fn (User $user): bool => in_array(
            $user->role?->designation,
            ['Super Admin', 'Comptable'],
            true
        ));

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
