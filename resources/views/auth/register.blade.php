@extends('layouts.templatePara')

@section('content')
<main class="bg-light min-vh-100 py-4">
    <div class="container">

        {{-- Formulaire Création d'Utilisateur --}}
        <div class="card border-0 rounded-4 mb-4">
            <div class="card-header bg-primary text-white text-center fw-semibold fs-5">
                <i class="bi bi-person-plus"></i> Inscription d'un Nouvel Utilisateur
            </div>
            <div class="card-body">

                {{-- Messages --}}
                @if(session('success'))
                    <script>
                        Swal.fire('Succès!', '{{ session('success') }}', 'success');
                    </script>
                @endif
                @if(session('error'))
                    <script>
                        Swal.fire('Erreur!', '{{ session('error') }}', 'error');
                    </script>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Formulaire POST vers register.index --}}
                <form action="{{ route('register.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control" required value="{{ old('nom') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="prenom" class="form-control" required value="{{ old('prenom') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Téléphone <span class="text-danger">*</span></label>
                        <input type="text" name="telephone" class="form-control" required value="{{ old('telephone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Rôle <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="">-- Sélectionner --</option>

                            @php
                                // Liste des rôles de base
                                $roles = ['admin','caissier','caissière','tresorier','tresorière','comptable','manager','facturier','facturière'];

                                // Ajouter super_admin si l'utilisateur connecté est super_admin
                                if($currentUser->role === 'super_admin') {
                                    array_unshift($roles, 'super_admin'); // Ajoute au début
                                }
                            @endphp

                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ old('role') == $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
    <label class="form-label fw-semibold">Entreprise <span class="text-danger">*</span></label>

    @if($currentUser->role === 'super_admin')
        <select class="form-control" name="entreprise_id">
            @foreach(\App\Models\Entreprise::all() as $entreprise)
                <option value="{{ $entreprise->id }}" 
                    {{ $currentUser->entreprise_id == $entreprise->id ? 'selected' : '' }}>
                    {{ $entreprise->nom_entreprise }}
                </option>
            @endforeach
        </select>
    @else
        <input type="text" class="form-control" value="{{ $currentUser->entreprise->nom_entreprise ?? 'N/A' }}" disabled>
    @endif

</div>

                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                </div>
            </form>

            </div>
        </div>

        {{-- Tableau des Utilisateurs --}}
        <div class="card border-0 rounded-4">
            <div class="card-header bg-secondary text-white fw-semibold fs-6 text-center">
                <i class="bi bi-person-lines-fill"></i> Liste des Utilisateurs
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-bordered table-hover align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Rôle</th>
                            <th>Entreprise</th>
                            <th>Dernière Connexion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $usersToShow = $currentUser->role === 'super_admin'
                                            ? $users // super_admin voit tous les utilisateurs
                                            : $users->where('role', '!=', 'super_admin'); // les autres ne voient pas les super_admin
                        @endphp     
                        @foreach($usersToShow  as $user)
                            <tr>
                                <td class="text-center">{{ $loop->iteration + ($users->currentPage()-1)*$users->perPage() }}</td>
                                <td>{{ $user->nom }}</td>
                                <td>{{ $user->prenom }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->telephone }}</td>
                                <td>{{ ucfirst($user->role) }}</td>
                                <td>{{ $user->entreprise->nom_entreprise ?? 'N/A' }}</td>
                                <td class="text-center">
                                    {{ $user->last_logged_in ? $user->last_logged_in->format('d/m/Y à H:i:s') : 'Jamais connecté' }}
                                </td>
                                <td class="text-center d-flex gap-2 justify-content-center">

                                    <!-- Bouton Modifier avec Modal -->
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $user->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Formulaire Supprimer -->
                                    <form id="delete-form-{{ $user->id }}" action="{{ route('register.destroy', $user->id) }}" method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete({{ $user->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                    <!-- Modal Modifier -->
                                    <div class="modal fade" id="editModal{{ $user->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $user->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning text-white">
                                                    <h5 class="modal-title" id="editModalLabel{{ $user->id }}">Modifier l'Utilisateur</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="{{ route('register.update', $user->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                                                                <input type="text" name="nom" class="form-control" required value="{{ old('nom', $user->nom) }}">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label>
                                                                <input type="text" name="prenom" class="form-control" required value="{{ old('prenom', $user->prenom) }}">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                                                <input type="email" name="email" class="form-control" required value="{{ old('email', $user->email) }}">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Téléphone <span class="text-danger">*</span></label>
                                                                <input type="text" name="telephone" class="form-control" required value="{{ old('telephone', $user->telephone) }}">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Rôle <span class="text-danger">*</span></label>
                                                                <select name="role" class="form-select" required>
                                                                    @foreach(['super_admin','admin','caissier','caissière','tresorier','tresorière','comptable','manager','facturier','facturière'] as $role)
                                                                        <option value="{{ $role }}" {{ old('role', $user->role)==$role?'selected':'' }}>{{ ucfirst($role) }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="text-center mt-3">
                                                            <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Enregistrer</button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                
                                @if($user->is_active == 1)
                                    <form action="{{ route('register.toggleStatus', $user->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success" title="Désactiver l'utilisateur">
                                            <i class="bi bi-toggle-on"></i> Actif
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('register.toggleStatus', $user->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-secondary" title="Activer l'utilisateur">
                                            <i class="bi bi-toggle-off"></i> Désactivé
                                        </button>
                                    </form>
                                @endif
                            </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Pagination --}}
                <div class="mt-3 d-flex justify-content-center">
                    {{ $users->links() }}
                </div>
            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmDelete(id) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Vous êtes sur le point de supprimer cet utilisateur !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + id).submit();
        }
    });
    }

// Actualiser la page toutes les 5 minutes
    setTimeout(() => location.reload(), 300000);
</script>

{{-- il va me renvoye le mot de passe par defaut --}}
@if(session('password_default'))
    <div id="passwordMessage" class="alert alert-info">
        {!! session('password_default') !!}
    </div>

    <script>
        // Délai de 3 minutes (180000 ms)
        setTimeout(function() {
            // Cacher le message après 3 minutes
            document.getElementById('passwordMessage').style.display = 'none';
        }, 180000);
    </script>
@endif
@endsection
