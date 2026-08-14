<div class="sidebar" id="sidebar">

<ul class="nav flex-column">

@php
    $role = strtolower($user->role?->designation ?? '');
@endphp



{{-- ADMINISTRATION --}}
@if($user->isSuperAdmin() || $user->isManagement())

    <li>
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active-menu' : '' }}">

            <i class="bi bi-speedometer2 me-2"></i>
            Tableau de bord

        </a>
    </li>


    @include('layouts.partials.etat-besoins')
    @include('layouts.partials.sortie-caisses')
    @include('layouts.partials.entree-caisses')
    @include('layouts.partials.brc')
    @include('layouts.partials.journaux')
    @if(config('features.accounting')) @include('layouts.partials.comptabilite') @endif


@endif




{{-- DIRECTION : Etat de besoin uniquement --}}
@if($user->hasRole(['Chef de Service', 'Directeur Technique', 'Chef de Département']))

    @include('layouts.partials.etat-besoins')

    @if($user->hasRole('Directeur Technique'))
        <li>
            <a href="{{ route('journaux.index') }}"
               class="nav-link {{ request()->routeIs('journaux.index') ? 'active-menu' : '' }}">
                <i class="bi bi-journal-bookmark me-2"></i>
                Journaux
            </a>
        </li>
    @endif

@endif




{{-- CAISSE --}}
@if(in_array($role, [

    'caissier',
    'caissière',
    'trésorier',
    'trésorière'

]))

    @include('layouts.partials.journaux')

@endif




{{-- COMPTABILITE --}}
@if($user->isAccounting())

    <li>
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active-menu' : '' }}">
            <i class="bi bi-speedometer2 me-2"></i>
            Tableau de bord
        </a>
    </li>

    @include('layouts.partials.etat-besoins')
    @include('layouts.partials.sortie-caisses')
    @include('layouts.partials.entree-caisses')
    @include('layouts.partials.brc')
    @include('layouts.partials.journaux')
    @if(config('features.accounting')) @include('layouts.partials.comptabilite') @endif

@endif



</ul>

</div>
