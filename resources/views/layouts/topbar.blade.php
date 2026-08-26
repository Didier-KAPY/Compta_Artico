<style>
    .topbar-notification-btn { position: relative; }
    .topbar-notification-badge { position: absolute; top: -4px; right: -5px; min-width: 19px; height: 19px; padding: 0 5px; display: inline-flex; align-items: center; justify-content: center; border: 2px solid #fff; border-radius: 999px; background: #dc3545; color: #fff; font-size: .65rem; font-weight: 700; line-height: 1; }
    .topbar-notification-menu { width: min(390px, calc(100vw - 24px)); max-height: 520px; padding: 0; overflow: hidden; border: 0; border-radius: 15px; box-shadow: 0 18px 50px rgba(15, 23, 42, .2); }
    .topbar-notification-head { padding: 15px 17px; background: #fff; border-bottom: 1px solid #e9edf3; }
    .topbar-notification-list { max-height: 390px; overflow-y: auto; }
    .topbar-notification-item { display: flex; gap: 11px; padding: 12px 16px; color: #1f2937; text-decoration: none; border-bottom: 1px solid #edf0f4; }
    .topbar-notification-item:hover { color: #111827; background: #f8fafc; }
    .topbar-notification-icon { width: 37px; height: 37px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 37px; border-radius: 10px; }
    .topbar-notification-empty { padding: 34px 20px; color: #6b7280; text-align: center; }
</style>
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
                    <button class="topbar-icon-btn topbar-notification-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ouvrir les notifications">
                        <i class="bi bi-bell-fill"></i>
                        @if($notifications['en_attente'] > 0)
                            <span class="topbar-notification-badge">{{ $notifications['en_attente'] > 99 ? '99+' : $notifications['en_attente'] }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end topbar-notification-menu">
                        <div class="topbar-notification-head d-flex justify-content-between align-items-center gap-3">
                            <div><strong class="d-block">Notifications</strong><small class="text-muted">Suivi des opérations</small></div>
                            <div class="d-flex gap-1">
                                <span class="badge bg-warning text-dark">{{ $notifications['en_attente'] }} en attente</span>
                                <span class="badge bg-success">{{ $notifications['valides'] }} validé{{ $notifications['valides'] > 1 ? 's' : '' }}</span>
                            </div>
                        </div>
                        @if($notifications['items']->isEmpty())
                            <div class="topbar-notification-empty">
                                <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
                                Aucune notification à signaler.
                            </div>
                        @else
                            <div class="topbar-notification-list">
                                @foreach($notifications['items']->take(15) as $notification)
                                    <a class="topbar-notification-item" href="{{ $notification['url'] }}">
                                        <span class="topbar-notification-icon {{ $notification['statut'] === 'En attente' ? 'bg-warning bg-opacity-10 text-warning' : 'bg-success bg-opacity-10 text-success' }}">
                                            <i class="bi {{ $notification['icon'] }}"></i>
                                        </span>
                                        <span class="flex-grow-1 overflow-hidden">
                                            <span class="d-flex justify-content-between gap-2">
                                                <strong class="text-truncate">{{ $notification['title'] }}</strong>
                                                <span class="badge {{ $notification['statut'] === 'En attente' ? 'bg-warning text-dark' : 'bg-success' }}">{{ $notification['statut'] }}</span>
                                            </span>
                                            <small class="text-muted d-block text-truncate">{{ $notification['module_label'] }} — {{ $notification['description'] }}</small>
                                            <small class="text-muted">{{ $notification['date']->diffForHumans() }}</small>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

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
