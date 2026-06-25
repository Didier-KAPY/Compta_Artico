<li class="nav-item mb-1">

    <!-- MENU PRINCIPAL -->
    <div class="nav-link text-white d-flex align-items-center justify-content-between menu-parent">

        <div class="d-flex align-items-center">

            <i class="bi bi-cash-stack fs-5 me-2"></i>

            <span class="menu-text">
                Trésorerie
            </span>

        </div>

        <i class="bi {{ request()->routeIs('tresorerie.*') ? 'bi-chevron-up' : 'bi-chevron-down' }} submenu-icon"
           data-target="#subTresorerie">
        </i>

    </div>

    <!-- SOUS MENU -->
    <ul class="submenu list-unstyled ps-3 mt-2"
        id="subTresorerie"
        style="{{ request()->routeIs('tresorerie.*') ? 'display:block;' : 'display:none;' }}">

        <!-- FACTURES -->
        <li class="mb-1">
            <a href="{{ route('factures.index') }}"
               class="nav-link text-white d-flex align-items-center {{ request()->routeIs('factures.*') ? 'active' : '' }}">
                <i class="bi bi-receipt me-2"></i>
                Factures
            </a>
        </li>

        <!-- PAIEMENTS -->
        <li class="mb-1">
            <a href="{{ route('paiements.index') }}"
               class="nav-link text-white d-flex align-items-center {{ request()->routeIs('paiements.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card me-2"></i>
                Paiements
            </a>
        </li>

        <!-- JOURNAL CAISSE -->
        <li class="mb-1">
            <a href="{{ route('tresorerie.journal-caisse') }}"
               class="nav-link text-white d-flex align-items-center {{ request()->routeIs('tresorerie.journal-caisse') ? 'active' : '' }}">
                <i class="bi bi-journal-text me-2"></i>
                Journal Caisse
            </a>
        </li>

        <!-- JOURNAL BANQUE -->
        <li class="mb-1">
            <a href=""
               class="nav-link text-white d-flex align-items-center {{ request()->routeIs('tresorerie.journal-banque') ? 'active' : '' }}">
                <i class="bi bi-bank me-2"></i>
                Journal Banque
            </a>
        </li>

        <!-- MOBILE MONEY -->
        <li class="mb-1">
            <a href=""
               class="nav-link text-white d-flex align-items-center">
                <i class="bi bi-phone me-2"></i>
                Journal Mobile Money
            </a>
        </li>

        <!-- COMPTES DE TRESORERIE -->
        <li class="mb-1">
            <a href=""
               class="nav-link text-white d-flex align-items-center {{ request()->routeIs('comptes.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2 me-2"></i>
                Comptes de Trésorerie
            </a>
        </li>

       
        <!-- LISTE JOURNAL BANQUE -->
        <li class="mb-1">
            <a href=""
               class="nav-link text-white d-flex align-items-center {{ request()->routeIs('tresorerie.liste-journal-banque') ? 'active' : '' }}">
                <i class="bi bi-card-list me-2"></i>
                Liste Journal Banque
            </a>
        </li>

        <!-- RAPPORT TRESORERIE -->
        <li class="mb-1">
            <a href=""
               class="nav-link text-white d-flex align-items-center">
                <i class="bi bi-graph-up me-2"></i>
                Rapport Trésorerie
            </a>
        </li>

    </ul>

</li>