<nav class="navbar navbar-light bg-white shadow-sm fixed-top px-3" style="z-index:1200;">

    <!-- Hamburger -->
    <button class="btn btn-light d-lg-none" id="toggleSidebar">
        <i class="bi bi-list fs-3"></i>
    </button>

    <!-- Logo Entreprise -->
    <div class="d-flex align-items-center ms-2">

        @if(!empty($entreprise?->logo))

            <img src="{{ asset('storage/'.$entreprise->logo) }}"
                alt="Logo entreprise"
                style="width:40px;height:40px;object-fit:cover;border-radius:50%;">

        @else

            <i class="bi bi-building fs-4"></i>

        @endif

        <span class="fw-bold">
            {{ $entreprise->nom_entreprise ?? 'COMPTA ARTICO' }}
        </span>

    </div>

    <!-- Partie droite -->
    <div class="ms-auto d-flex align-items-center">

        @if($user)
            <small class="d-none d-md-block me-3">
                {{ $user->prenom }} {{ $user->nom }}
                -
                {{ $user->role?->designation ?? 'Sans rôle' }}
                @if($user->role?->type)
                    ({{ $user->role->type }})
                @endif
            </small>
        @endif
        <!-- Profil -->
        <div class="dropdown">

            <a href="#"
               class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark"
               data-bs-toggle="dropdown">

                @if(!empty($user?->photo))
                    <img src="{{ asset('storage/'.$user->photo) }}"
                         alt="Photo Profil"
                         class="rounded-circle me-2"
                         style="width:35px;height:35px;object-fit:cover;">
                @else
                    <i class="bi bi-person-circle fs-3 me-2"></i>
                @endif

                <span class="d-none d-md-inline">
                    Profil
                </span>

            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow">

                <li class="dropdown-header">
                    <strong>{{ $user->prenom ?? '' }} {{ $user->nom ?? '' }}</strong><br>
                    <small class="text-muted">{{ $user->email ?? '' }}</small>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a class="dropdown-item" href="{{ route('profil.index') }}">
                        <i class="bi bi-person me-2"></i>
                        Mon profil
                    </a>
                </li>

                <li>
                    <a class="dropdown-item" href="{{ route('parametres.parametre') }}">
                        <i class="bi bi-gear me-2"></i>
                        Paramètres
                    </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Déconnexion
                        </button>
                    </form>
                </li>

            </ul>

        </div>

    </div>

</nav>