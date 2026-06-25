<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Entreprise;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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