<li class="nav-item mb-1">

    @php
        $isActive = request()->routeIs([
            'ecritures.*',
            'grandlivre.*',
            'balance.*',
            'comptabilite.etats-financiers.*'
        ]);
    @endphp

    <!-- MENU COMPTABILITÉ -->
    <div class="nav-link text-white d-flex align-items-center justify-content-between menu-parent"
         style="cursor:pointer;">

        <div class="d-flex align-items-center">
            <i class="bi bi-calculator fs-5 me-2"></i>

            <span>
                Comptabilité
            </span>
        </div>

        <i class="bi bi-chevron-down submenu-icon {{ $isActive ? 'rotate' : '' }}"></i>

    </div>


    <!-- SOUS MENU -->
    <ul class="submenu list-unstyled ps-3 mt-2 {{ $isActive ? 'show' : '' }}">


        <!-- ECRITURES COMPTABLES -->
        <li>

            <a href="{{ route('ecritures.liste') }}"
               class="nav-link {{ request()->routeIs('ecritures.liste') ? 'active-menu' : '' }}">

                <i class="bi bi-journal-text me-2"></i>

                Ecritures Comptables

            </a>

        </li>



        <!-- GRAND LIVRE -->
        <li>

            <a href="{{ route('grandlivre.index') }}"
               class="nav-link {{ request()->routeIs('grandlivre.index') ? 'active-menu' : '' }}">

                <i class="bi bi-book me-2"></i>

                Grand Livre général

            </a>

        </li>



        <!-- BALANCE -->
        <li>

            <a href="{{ route('balance.index') }}"
               class="nav-link {{ request()->routeIs('balance.index') ? 'active-menu' : '' }}">

                <i class="bi bi-bar-chart-line me-2"></i>

                Balance

            </a>

        </li>
        @can('viewAccountingReports')
            <li class="mt-2 px-3 text-uppercase small text-secondary">États financiers</li>
            <li>
                <a href="{{ route('comptabilite.etats-financiers.bilan') }}"
                   class="nav-link {{ request()->routeIs('comptabilite.etats-financiers.bilan*') ? 'active-menu' : '' }}">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                    Bilan final
                </a>
            </li>
            <li>
                <a href="{{ route('comptabilite.etats-financiers.compte-resultat') }}"
                   class="nav-link {{ request()->routeIs('comptabilite.etats-financiers.compte-resultat*') ? 'active-menu' : '' }}">
                    <i class="bi bi-graph-up-arrow me-2"></i>
                    Compte de résultat
                </a>
            </li>
        @endcan


    </ul>

</li>
