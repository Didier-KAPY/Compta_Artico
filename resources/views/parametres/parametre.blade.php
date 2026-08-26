@extends('layouts.app')

@section('title', 'Paramètres')

@section('content')
@php
    $user = auth()->user();
    $isSuperAdmin = $user->isSuperAdmin();
    $isAdmin = $user->isSuperAdmin() || $user->isManagement();
    $isDirection = $user->isManagement();
    $isComptabilite = $user->hasRole(['Comptable', 'DAF']);
    $peutGererCartes = $user->can('manageServiceCards');

    $rubriques = collect([
        ['visible' => $isAdmin, 'route' => 'parametres.entreprise', 'icon' => 'bi-building', 'color' => 'primary', 'title' => 'Entreprise', 'text' => 'Identité, coordonnées et informations légales.'],
        ['visible' => $isAdmin, 'route' => 'parametres.departements', 'icon' => 'bi-diagram-3', 'color' => 'info', 'title' => 'Directions et fonctions', 'text' => 'Structure organisationnelle et affectation des agents.'],
        ['visible' => $isAdmin, 'route' => 'parametres.utilisateurs', 'icon' => 'bi-people', 'color' => 'primary', 'title' => 'Utilisateurs', 'text' => 'Créer les comptes et consulter les dernières connexions.'],
        ['visible' => $isAdmin || $user->hasRole('DAF'), 'route' => 'parametres.rh.index', 'icon' => 'bi-person-workspace', 'color' => 'info', 'title' => 'Gestion des ressources humaines', 'text' => 'Employés, contrats, présences, congés, paie et évaluations.'],
        ['visible' => $peutGererCartes, 'route' => 'parametres.cartes-service.index', 'icon' => 'bi-person-badge', 'color' => 'success', 'title' => 'Cartes de service', 'text' => 'Créer, imprimer et télécharger les cartes PVC du personnel.'],
        ['visible' => config('features.accounting') && ($isSuperAdmin || $isComptabilite), 'route' => 'parametres.comptes', 'icon' => 'bi-journal-bookmark', 'color' => 'success', 'title' => 'Plan comptable', 'text' => 'Comptes généraux utilisés dans les écritures.'],
        ['visible' => config('features.accounting') && ($isSuperAdmin || $isComptabilite), 'route' => 'parametres.journal-types.create', 'icon' => 'bi-journal-text', 'color' => 'warning', 'title' => 'Types de journaux', 'text' => 'Codes journaux et comptes de trésorerie associés.'],
        ['visible' => config('features.accounting') && ($isSuperAdmin || $isComptabilite), 'route' => 'parametres.taux-change.create', 'icon' => 'bi-currency-exchange', 'color' => 'danger', 'title' => 'Taux de change', 'text' => 'Cours appliqué aux opérations multidevises.'],
        ['visible' => config('features.accounting') && ($isSuperAdmin || $isComptabilite), 'route' => 'parametres.comptables.index', 'icon' => 'bi-calculator', 'color' => 'secondary', 'title' => 'Paramétrage comptable', 'text' => 'Comptes automatiques et règles de comptabilisation.'],
        ['visible' => $isSuperAdmin, 'route' => 'parametres.audit.index', 'icon' => 'bi-shield-check', 'color' => 'danger', 'title' => 'Suivi et alertes comptables', 'text' => 'Surveiller les suppressions, restaurations et opérations sensibles.'],
        ['visible' => config('features.accounting') && $isSuperAdmin, 'route' => 'parametres.clotures.index', 'icon' => 'bi-calendar2-check', 'color' => 'primary', 'title' => 'Clôtures journalières', 'text' => 'Simuler, lancer, vérifier et suivre les clôtures comptables.'],
        ['visible' => config('features.accounting') && $isSuperAdmin, 'route' => 'parametres.periodes.index', 'icon' => 'bi-calendar-range', 'color' => 'warning', 'title' => 'Périodes comptables', 'text' => 'Clôturer ou réouvrir les mois et exercices comptables.'],
        ['visible' => config('features.budget') && ($isSuperAdmin || $user->hasRole('DAF')), 'route' => 'parametres.budgets.index', 'icon' => 'bi-pie-chart', 'color' => 'success', 'title' => 'Budgets', 'text' => 'Prévisions, réalisations, écarts et pourcentages par compte et direction.'],
        ['visible' => config('features.accounting') && ($isSuperAdmin || $isComptabilite), 'route' => 'parametres.imports.index', 'icon' => 'bi-file-earmark-arrow-up', 'color' => 'info', 'title' => 'Imports comptables', 'text' => 'Prévisualiser et importer le plan comptable depuis un fichier CSV.'],
        ['visible' => $isSuperAdmin, 'route' => 'parametres.sauvegardes.index', 'icon' => 'bi-database-check', 'color' => 'danger', 'title' => 'Sauvegardes', 'text' => 'Créer, télécharger et restaurer les sauvegardes MySQL.'],
    ])->where('visible');
@endphp

<style>
    .settings-hero { background: linear-gradient(135deg, #0f2744, #185c91); border-radius: 18px; color: #fff; }
    .settings-card { border: 1px solid #e8edf3; border-radius: 14px; transition: .2s ease; }
    .settings-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(15,39,68,.10); border-color: transparent; }
    .settings-icon { width: 52px; height: 52px; display: inline-flex; align-items: center; justify-content: center; border-radius: 13px; font-size: 1.35rem; }
</style>

<div class="container-fluid py-4 px-lg-4">
    <div class="settings-hero p-4 p-lg-5 mb-4 shadow-sm">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div><span class="badge bg-white text-primary mb-2">Administration</span><h2 class="fw-bold mb-2">Paramètres du système</h2><p class="mb-0 text-white-50">Configurez les référentiels et les règles de fonctionnement de l’application.</p></div>
            <i class="bi bi-sliders2 display-4 opacity-50"></i>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div><h5 class="fw-bold mb-1">Configurations disponibles</h5><small class="text-muted">Les accès affichés dépendent de votre rôle.</small></div>
        <span class="badge rounded-pill bg-light text-dark border">{{ $rubriques->count() }} rubrique(s)</span>
    </div>

    <div class="row g-4">
        @forelse($rubriques as $rubrique)
            <div class="col-sm-6 col-xl-4">
                <a href="{{ route($rubrique['route']) }}" class="text-decoration-none text-dark">
                    <div class="card settings-card h-100 shadow-sm"><div class="card-body p-4">
                        <div class="settings-icon bg-{{ $rubrique['color'] }} bg-opacity-10 text-{{ $rubrique['color'] }} mb-3"><i class="bi {{ $rubrique['icon'] }}"></i></div>
                        <h5 class="fw-bold">{{ $rubrique['title'] }}</h5><p class="text-muted mb-3">{{ $rubrique['text'] }}</p>
                        <span class="small fw-semibold text-primary">Ouvrir <i class="bi bi-arrow-right ms-1"></i></span>
                    </div></div>
                </a>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-light border">Aucune configuration n’est disponible pour votre rôle.</div></div>
        @endforelse
    </div>
</div>
@endsection
