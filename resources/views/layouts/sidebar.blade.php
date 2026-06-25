<div class="sidebar" id="sidebar">

    <ul class="nav flex-column">


        @php
            $role = strtolower($user->role?->designation ?? '');
        @endphp



        {{-- DASHBOARD uniquement administrateur --}}
        @if(in_array($role, [
            'super admin',
            'admin',
            'manager'
        ]))

            <li>
                <a href="{{ route('dashboard') }}"
                   class="nav-link {{ request()->routeIs('dashboard') ? 'active-menu' : '' }}">

                    <i class="bi bi-speedometer2 me-2"></i>

                    Dashboard

                </a>
            </li>

        @endif
        {{-- MENUS GENERAUX : ADMIN + CAISSIER --}}
        @if(in_array($role, [
            'super admin',
            'admin',
            'manager',
            'caissier'
        ]))
            @include('layouts.partials.etat-besoins')
            @include('layouts.partials.sortie-caisses')
            @include('layouts.partials.entree-caisses')
            @include('layouts.partials.journaux')


        @endif



    </ul>


</div>