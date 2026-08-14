@extends('layouts.app')
@section('title', 'Gestion des utilisateurs')
@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div><h3 class="fw-bold mb-1"><i class="bi bi-people text-primary me-2"></i>Gestion des utilisateurs</h3><p class="text-muted mb-0">Créez les accès et consultez l’activité de connexion des comptes.</p></div>
        <a href="{{ route('parametres.parametre') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Paramètres</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
        @if(session('password_default'))<div class="alert alert-warning"><strong>Identifiants temporaires :</strong> {{ session('agent_email') }} — Mot de passe : <span class="badge bg-dark fs-6">{{ session('password_default') }}</span><div class="small mt-1">À transmettre de façon sécurisée. Le changement sera exigé à la première connexion.</div></div>@endif
    @endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"><div><strong><i class="bi bi-person-plus text-primary me-2"></i>Nouvel utilisateur</strong><small class="d-block text-muted">Un mot de passe temporaire sera généré automatiquement.</small></div><button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#userForm"><i class="bi bi-plus-lg me-1"></i>Créer</button></div>
        <div class="collapse {{ $errors->any() ? 'show' : '' }}" id="userForm"><div class="card-body p-4">
            <form action="{{ route('profil.user.store') }}" method="POST" enctype="multipart/form-data">@csrf<input type="hidden" name="source" value="parametres">
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Nom *</label><input name="nom" value="{{ old('nom') }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Prénom *</label><input name="prenom" value="{{ old('prenom') }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">E-mail *</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Téléphone *</label><input name="telephone" value="{{ old('telephone') }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Rôle *</label><select name="role_id" class="form-select" required><option value="">Sélectionner</option>@foreach($roles as $role)@if(auth()->user()->isSuperAdmin() || $role->designation !== 'Super Admin')<option value="{{ $role->id }}" @selected(old('role_id')==$role->id)>{{ $role->designation }}</option>@endif @endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Département</label><select name="departement_id" class="form-select"><option value="">Non affecté</option>@foreach($departements as $d)<option value="{{ $d->id }}" @selected(old('departement_id')==$d->id)>{{ $d->designation }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Fonction</label><select name="fonction_id" class="form-select"><option value="">Non affectée</option>@foreach($fonctions as $f)<option value="{{ $f->id }}" @selected(old('fonction_id')==$f->id)>{{ $f->designation }}</option>@endforeach</select></div>
                    <div class="col-md-5"><label class="form-label">Adresse</label><input name="adresse" value="{{ old('adresse') }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Photo</label><input type="file" name="photo" accept="image/*" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Signature</label><input type="file" name="signature" accept="image/png" class="form-control"><div class="form-text">PNG avec fond transparent.</div></div>
                    <div class="col-md-3"><label class="form-label">Statut</label><select name="statut" class="form-select"><option>Actif</option><option @selected(old('statut')==='Inactif')>Inactif</option></select></div>
                </div><div class="text-end mt-3"><button class="btn btn-primary px-4"><i class="bi bi-person-check me-2"></i>Enregistrer</button></div>
            </form>
        </div></div>
    </div>

    <div class="card border-0 shadow-sm"><div class="card-header bg-dark text-white py-3 d-flex justify-content-between"><strong>Liste des utilisateurs</strong><span class="badge bg-light text-dark">{{ $users->total() }} compte(s)</span></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Utilisateur</th><th>Rôle</th><th>Affectation</th><th>Statut</th><th>Dernière connexion</th><th class="text-end">Actions</th></tr></thead><tbody>
        @forelse($users as $agent)<tr><td><div class="fw-semibold">{{ $agent->prenom }} {{ $agent->nom }}</div><small class="text-muted">{{ $agent->email }} @if($agent->telephone)· {{ $agent->telephone }}@endif</small></td><td><form action="{{ route('parametres.utilisateurs.role', $agent) }}" method="POST" class="d-flex gap-2">@csrf @method('PATCH')<select name="role_id" class="form-select form-select-sm" aria-label="Rôle de {{ $agent->prenom }} {{ $agent->nom }}" @disabled($agent->isSuperAdmin() && !auth()->user()->isSuperAdmin())>@foreach($roles as $role)@if(auth()->user()->isSuperAdmin() || $role->designation !== 'Super Admin')<option value="{{ $role->id }}" @selected($agent->role_id===$role->id)>{{ $role->designation }}</option>@endif @endforeach</select><button class="btn btn-sm btn-outline-primary" title="Enregistrer le rôle" @disabled($agent->isSuperAdmin() && !auth()->user()->isSuperAdmin())><i class="bi bi-check-lg"></i></button></form></td><td>{{ $agent->departement?->designation ?? '—' }}<small class="d-block text-muted">{{ $agent->fonction?->designation ?? 'Aucune fonction' }}</small></td><td><span class="badge bg-{{ $agent->statut==='Actif'?'success':'secondary' }}">{{ $agent->statut }}</span></td><td>@if($agent->last_logged_in)<span class="fw-semibold">{{ \Carbon\Carbon::parse($agent->last_logged_in)->format('d/m/Y H:i') }}</span><small class="d-block text-muted">{{ \Carbon\Carbon::parse($agent->last_logged_in)->diffForHumans() }}</small>@else<span class="text-muted">Jamais connecté</span>@endif</td><td class="text-end"><form action="{{ route('parametres.utilisateurs.password.reset', $agent) }}" method="POST" data-confirm="Réinitialiser le mot de passe de cet utilisateur ?">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning text-nowrap" @disabled($agent->isSuperAdmin() && !auth()->user()->isSuperAdmin())><i class="bi bi-key me-1"></i>Réinitialiser</button></form></td></tr>
        @empty<tr><td colspan="6" class="text-center text-muted py-4">Aucun utilisateur.</td></tr>@endforelse
    </tbody></table></div>@if($users->hasPages())<div class="card-footer bg-white">{{ $users->links() }}</div>@endif</div>
</div>
@endsection
