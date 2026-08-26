<li class="nav-item mb-1">

    @php
        $isActive = request()->routeIs('journaux.*', 'comptabilite.imputation-compte*');
    @endphp


    <!-- MENU PRINCIPAL -->
    <div class="nav-link text-white d-flex align-items-center justify-content-between menu-parent"
         style="cursor:pointer;">


        <div class="d-flex align-items-center">

            <i class="bi bi-journal-bookmark fs-5 me-2"></i>
            <span>
                Journaux
            </span>
        </div>
        <i class="bi bi-chevron-down submenu-icon {{ $isActive ? 'rotate' : '' }}"></i>
    </div>
    <!-- SOUS MENU -->
    <ul class="submenu list-unstyled ps-3 mt-2 {{ $isActive ? 'show' : '' }}">
        @can('manageJournaux')
        @if(auth()->user()->hasRole(['Super Admin', 'Admin', 'Comptable']))
        <li>
            <a href="{{ route('comptabilite.imputation-compte') }}" class="nav-link {{ request()->routeIs('comptabilite.imputation-compte*') ? 'active-menu' : '' }}">
                <i class="bi bi-table me-2"></i>Journal des opérations diverses
            </a>
        </li>
        @endif
        <!-- Nouveaux journaux de trésorerie -->
        <li>
            <a href="{{ route('journaux.create.caisse') }}" class="nav-link {{ request()->routeIs('journaux.create.caisse') ? 'active-menu' : '' }}">
                <i class="bi bi-cash-stack me-2"></i>Journal Caisse
            </a>
        </li>
        <li>
            <a href="{{ route('journaux.create.banque') }}" class="nav-link {{ request()->routeIs('journaux.create.banque') ? 'active-menu' : '' }}">
                <i class="bi bi-bank me-2"></i>Journal Banque
            </a>
        </li>
        <li>
            <a href="{{ route('journaux.create.mobile') }}" class="nav-link {{ request()->routeIs('journaux.create.mobile') ? 'active-menu' : '' }}">
                <i class="bi bi-phone me-2"></i>Journal Mobile Money
            </a>
        </li>

        @endcan
        <!-- Relevé -->
        <li class="nav-item">
            <a class="nav-link"
            href="{{ route('journaux.releve') }}"
            class="nav-link {{ request()->routeIs('journaux.releve') ? 'active-menu' : '' }}">
                <i class="bi bi-journal-text me-2"></i>
                    Relevé Journal
            </a>

        </li>
        <!-- Situation trésorerie -->
        @can('viewTreasurySituation')
        <li>
            <a href="{{ route('journaux.tresorerie') }}"
               class="nav-link {{ request()->routeIs('journaux.tresorerie') ? 'active-menu' : '' }}">
                <i class="bi bi-pie-chart-fill me-2"></i>
                Situation trésorerie
            </a>
        </li>
        @endcan
    </ul>
</li>
