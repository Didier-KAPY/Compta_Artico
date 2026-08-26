<li class="nav-item mb-1">

    @php
        $isActive = request()->routeIs('sortie-caisses.*');
        $canCreateSortie = in_array(
            auth()->user()?->role?->designation,
            ['Super Admin', 'Admin', 'Directeur Général', 'Caissier', 'Caissière', 'Trésorier', 'Trésorière'],
            true
        );
    @endphp

    <div class="nav-link text-white d-flex align-items-center justify-content-between menu-parent"
         style="cursor:pointer;">
        <div class="d-flex align-items-center">
            <i class="bi bi-cash-stack fs-5 me-2"></i>
            <span>Bon de sortie</span>
        </div>
        <i class="bi bi-chevron-down submenu-icon {{ $isActive ? 'rotate' : '' }}"></i>
    </div>

    <ul class="submenu list-unstyled ps-3 mt-2 {{ $isActive ? 'show' : '' }}">
        <li>
            <a href="{{ route('sortie-caisses.index') }}"
               class="nav-link {{ request()->routeIs('sortie-caisses.index') ? 'active-menu' : '' }}">
                <i class="bi bi-list-ul me-2"></i>
                Liste des bons de sortie
            </a>
        </li>

        @if($canCreateSortie)
        <li>
            <a href="{{ route('sortie-caisses.create') }}"
               class="nav-link {{ request()->routeIs('sortie-caisses.create') ? 'active-menu' : '' }}">
                <i class="bi bi-plus-circle me-2"></i>
                Nouveau bon de sortie
            </a>
        </li>
        @endif
    </ul>

</li>