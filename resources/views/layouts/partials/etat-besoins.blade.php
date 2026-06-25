
<li class="nav-item mb-1">

    <!-- MENU PRINCIPAL -->
    <div class="nav-link text-white d-flex align-items-center justify-content-between menu-parent"
         style="cursor:pointer;">

        <div class="d-flex align-items-center">
            <i class="bi bi-file-earmark-text fs-5 me-2"></i>
            <span>États de Besoin</span>
        </div>

        <i class="bi submenu-icon
            {{ request()->routeIs('etat-besoins.*') ? 'bi-chevron-up rotate' : 'bi-chevron-down' }}">
        </i>

    </div>

    <!-- SOUS MENU -->
    <ul class="submenu list-unstyled ps-3 mt-2
        {{ request()->routeIs('etat-besoins.*') ? 'show' : '' }}">

        <li>
            <a href="{{ route('etat-besoins.index') }}"
               class="nav-link {{ request()->routeIs('etat-besoins.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul me-2"></i>
                Liste des États de Besoin
            </a>
        </li>

        <li>
            <a href="{{ route('etat-besoins.create') }}"
               class="nav-link {{ request()->routeIs('etat-besoins.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle me-2"></i>
                Nouveau État de Besoin
            </a>
        </li>

    </ul>

</li>

