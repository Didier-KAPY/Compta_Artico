<li class="nav-item mb-1">

    @php
        $isActive = request()->routeIs('journaux.*');
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
        <!-- Liste journaux -->
        <li>
            <a href="{{ route('journaux.index') }}"
               class="nav-link {{ request()->routeIs('journaux.index') ? 'active-menu' : '' }}">
                <i class="bi bi-list-ul me-2"></i>
                Caisses
            </a>
        </li>
        <!-- Nouveau journal -->
        <li>
            <a href="{{ route('journaux.create') }}"
               class="nav-link {{ request()->routeIs('journaux.create') ? 'active-menu' : '' }}">
                <i class="bi bi-plus-circle me-2"></i>
                Nouveau journal
            </a>
        </li>
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
        <li>
            <a href="{{ route('journaux.tresorerie') }}"
               class="nav-link {{ request()->routeIs('journaux.tresorerie') ? 'active-menu' : '' }}">
                <i class="bi bi-pie-chart-fill me-2"></i>
                Situation trésorerie
            </a>
        </li>
    </ul>
</li>