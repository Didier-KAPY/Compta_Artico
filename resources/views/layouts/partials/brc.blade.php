<li class="nav-item mb-1">
    @php($brcActif = request()->routeIs('brc.*'))
    <div class="nav-link text-white d-flex align-items-center justify-content-between menu-parent" style="cursor:pointer;">
        <div class="d-flex align-items-center"><i class="bi bi-folder2-open me-2"></i><span>Bon de régulation comptable</span></div>
        <i class="bi bi-chevron-down submenu-icon {{ $brcActif ? 'rotate' : '' }}"></i>
    </div>
    <ul class="submenu list-unstyled ps-3 mt-2 {{ $brcActif ? 'show' : '' }}">
        <li><a href="{{ route('brc.index') }}" class="nav-link {{ request()->routeIs('brc.index', 'brc.show') ? 'active-menu' : '' }}"><i class="bi bi-file-earmark-text me-2"></i>BRC</a></li>
        @if(auth()->user()->hasRole(['Super Admin', 'Admin', 'Comptable', 'Chargé des finances']))
        <li><a href="{{ route('brc.create') }}" class="nav-link {{ request()->routeIs('brc.create') ? 'active-menu' : '' }}"><i class="bi bi-plus-circle me-2"></i>Nouveau BRC</a></li>
        @endif
    </ul>
</li>