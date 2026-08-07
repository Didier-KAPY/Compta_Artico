<nav class="navbar app-topbar fixed-top" aria-label="Navigation principale">
    <div class="container-fluid app-topbar-inner">
        <div class="d-flex align-items-center app-topbar-left">
            <button type="button" class="topbar-icon-btn d-lg-none" id="toggleSidebar" aria-label="Ouvrir le menu" aria-controls="sidebar" aria-expanded="false">
                <i class="bi bi-list"></i>
            </button>

            <a href="{{ $user?->hasRole(['Directeur Technique', 'Chef de Service', 'Chef de Département']) ? route('etat-besoins.index') : route('dashboard') }}" class="topbar-brand text-decoration-none">
                <span class="topbar-logo">
                    @if(!empty($entreprise?->logo))
                        <img src="{{ asset('storage/'.$entreprise->logo) }}" alt="Logo {{ $entreprise->nom_entreprise ?? 'entreprise' }}">
                    @else
                        <i class="bi bi-building"></i>
                    @endif
                </span>
                <span class="topbar-brand-copy">
                    <strong>{{ $entreprise->nom_entreprise ?? 'COMPTA ARTICO' }}</strong>
                    <small>Gestion comptable</small>
                </span>
            </a>
        </div>

        <div class="topbar-context d-none d-xl-flex">
            <span class="topbar-context-icon"><i class="bi bi-grid-1x2"></i></span>
            <div>
                <small>Espace de travail</small>
                <strong>@yield('title', 'Tableau de bord')</strong>
            </div>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
            <div class="topbar-date d-none d-md-flex">
                <i class="bi bi-calendar3"></i>
                <span>{{ now()->translatedFormat('d M Y') }}</span>
            </div>

            @if($user)
                @php
                    $nomComplet = trim(($user->prenom ?? '').' '.($user->nom ?? ''));
                    $initiales = mb_strtoupper(mb_substr($user->prenom ?? '', 0, 1).mb_substr($user->nom ?? '', 0, 1));
                    $peutConfigurer = $user->hasRole([
                        'Super Admin', 'Admin', 'Comptable', 'DAF', 'Directeur Technique',
                    ]);
                @endphp
                <div class="dropdown">
                    <button class="topbar-user" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="topbar-avatar">
                            @if(!empty($user->photo))
                                <img src="{{ asset('storage/'.$user->photo) }}" alt="Photo de {{ $nomComplet }}">
                            @else
                                <span>{{ $initiales ?: 'U' }}</span>
                            @endif
                            <i class="topbar-status" title="Compte actif"></i>
                        </span>
                        <span class="topbar-user-copy d-none d-md-block">
                            <strong>{{ $nomComplet ?: 'Utilisateur' }}</strong>
                            <small>{{ $user->role?->designation ?? 'Sans rôle' }}</small>
                        </span>
                        <i class="bi bi-chevron-down topbar-chevron"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end topbar-user-menu">
                        <div class="topbar-menu-head">
                            <span class="topbar-avatar topbar-avatar-lg">
                                @if(!empty($user->photo))
                                    <img src="{{ asset('storage/'.$user->photo) }}" alt="Photo de {{ $nomComplet }}">
                                @else
                                    <span>{{ $initiales ?: 'U' }}</span>
                                @endif
                            </span>
                            <div><strong>{{ $nomComplet ?: 'Utilisateur' }}</strong><small>{{ $user->email }}</small></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('profil.index') }}"><i class="bi bi-person"></i><span>Mon profil<small>Informations personnelles</small></span></a>
                        @if($peutConfigurer)
                            <a class="dropdown-item" href="{{ route('parametres.parametre') }}"><i class="bi bi-gear"></i><span>Paramètres<small>Configuration de l’application</small></span></a>
                        @endif
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item topbar-logout"><i class="bi bi-box-arrow-right"></i><span>Déconnexion<small>Fermer la session</small></span></button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</nav>
