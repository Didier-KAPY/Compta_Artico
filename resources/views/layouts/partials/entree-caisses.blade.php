<li class="nav-item mb-1">

    <!-- MENU PRINCIPAL -->
    <div class="nav-link text-white d-flex align-items-center justify-content-between menu-parent"
         style="cursor:pointer;">

        <div class="d-flex align-items-center">
            <i class="bi bi-cash-stack fs-5 me-2"></i>
            <span>Bon d'entrée</span>
        </div>

        <i class="bi submenu-icon
            {{ request()->routeIs('entree-caisses.*') ? 'bi-chevron-up rotate' : 'bi-chevron-down' }}">
        </i>

    </div>

    <!-- SOUS MENU -->
    <ul class="submenu list-unstyled ps-3 mt-2
        {{ request()->routeIs('entree-caisses.*') ? 'show' : '' }}">

        <!-- LISTE -->
        <li>
            <a href="{{ route('entree-caisses.index') }}"
               class="nav-link {{ request()->routeIs('entree-caisses.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul me-2"></i>
                Liste des Entrées
            </a>
        </li>

        <!-- CREATE -->
        <li>
            <a href="{{ route('entree-caisses.create') }}"
               class="nav-link {{ request()->routeIs('entree-caisses.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle me-2"></i>
                Nouvelle Entrée
            </a>
        </li>

        <!-- STATISTIQUES -->
        <li>
            <a href="{{ route('entree-caisses.statistiques') }}"
               class="nav-link {{ request()->routeIs('entree-caisses.statistiques') ? 'active' : '' }}">
                <i class="bi bi-graph-up me-2"></i>
                statistiques
            </a>
        </li>

    </ul>

</li>