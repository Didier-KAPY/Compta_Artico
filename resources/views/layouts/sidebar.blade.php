<div class="sidebar" id="sidebar">

<ul class="nav flex-column">

@php
    $role = strtolower($user->role?->designation ?? '');
@endphp



{{-- ADMINISTRATION --}}
@if(in_array($role, [

    'super admin',
    'admin',
    'directeur général',
    'gérant'


]))

    <li>
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active-menu' : '' }}">

            <i class="bi bi-speedometer2 me-2"></i>
            Dashboard

        </a>
    </li>


    @include('layouts.partials.etat-besoins')
    @include('layouts.partials.sortie-caisses')
    @include('layouts.partials.entree-caisses')
    @include('layouts.partials.journaux')
    @include('layouts.partials.comptabilite')


@endif




{{-- DIRECTION : Etat de besoin uniquement --}}
@if(in_array($role, [

    'chef de service',
    'directeur technique',
    'chef de département'

]))

    @include('layouts.partials.etat-besoins')

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
@if(in_array($role, [

    'comptable',
    'daf'

]))

    @include('layouts.partials.etat-besoins')
    @include('layouts.partials.sortie-caisses')
    @include('layouts.partials.entree-caisses')
    @include('layouts.partials.journaux')
    @include('layouts.partials.comptabilite')

@endif


</ul>

</div>