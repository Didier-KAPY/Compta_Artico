@extends('layouts.app')

@section('title', 'Nouvel utilisateur')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div><h3 class="fw-bold mb-1"><i class="bi bi-person-plus text-primary me-2"></i>Nouvel utilisateur</h3><p class="text-muted mb-0">Créez le compte et définissez son rattachement organisationnel.</p></div>
        <a href="{{ route('profil.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour aux utilisateurs</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle-fill fs-4 me-3"></i><div><strong>Compte créé.</strong><br>{{ session('success') }}</div></div>
        <div class="alert alert-warning"><div class="fw-bold mb-2"><i class="bi bi-key me-2"></i>Informations de connexion temporaires</div><span class="me-4">Email : <strong>{{ session('agent_email') }}</strong></span><span>Mot de passe : <span class="badge bg-dark fs-6">{{ session('password_default') }}</span></span><div class="small mt-2">Communiquez ces informations de manière sécurisée. Le mot de passe devra être changé à la première connexion.</div></div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger"><div class="fw-bold mb-1">Veuillez corriger les informations suivantes :</div><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('profil.user.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm h-100"><div class="card-header bg-white border-bottom py-3"><h5 class="fw-bold mb-0"><i class="bi bi-person-vcard text-primary me-2"></i>Informations personnelles</h5></div><div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label><input type="text" name="nom" value="{{ old('nom') }}" class="form-control @error('nom') is-invalid @enderror" required>@error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label><input type="text" name="prenom" value="{{ old('prenom') }}" class="form-control @error('prenom') is-invalid @enderror" required>@error('prenom')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Adresse e-mail <span class="text-danger">*</span></label><input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Téléphone <span class="text-danger">*</span></label><input type="text" name="telephone" value="{{ old('telephone') }}" class="form-control @error('telephone') is-invalid @enderror" required>@error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><label class="form-label fw-semibold">Adresse physique</label><input type="text" name="adresse" value="{{ old('adresse') }}" class="form-control @error('adresse') is-invalid @enderror">@error('adresse')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Photo de profil</label><input type="file" name="photo" accept="image/*" class="form-control @error('photo') is-invalid @enderror">@error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Image JPG, PNG ou WEBP, 2 Mo maximum.</div></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Statut</label><select name="statut" class="form-select"><option value="Actif" @selected(old('statut', 'Actif') === 'Actif')>Actif</option><option value="Inactif" @selected(old('statut') === 'Inactif')>Inactif</option></select></div>
                    </div>
                </div></div>
            </div>

            <div class="col-xl-4">
                <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-3"><h5 class="fw-bold mb-0"><i class="bi bi-diagram-3 text-primary me-2"></i>Accès et affectation</h5></div><div class="card-body p-4">
                    <div class="mb-3"><label class="form-label fw-semibold">Rôle <span class="text-danger">*</span></label><select name="role_id" class="form-select @error('role_id') is-invalid @enderror" required><option value="">Sélectionner un rôle</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->designation }}</option>@endforeach</select>@error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="mb-3"><label class="form-label fw-semibold">Département</label><select name="departement_id" class="form-select @error('departement_id') is-invalid @enderror"><option value="">Non affecté</option>@foreach($departements as $departement)<option value="{{ $departement->id }}" @selected((string) old('departement_id') === (string) $departement->id)>{{ $departement->designation }}</option>@endforeach</select>@error('departement_id')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Détermine notamment les états de besoin accessibles.</div></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Fonction</label><select name="fonction_id" class="form-select @error('fonction_id') is-invalid @enderror"><option value="">Non affectée</option>@foreach($fonctions as $fonction)<option value="{{ $fonction->id }}" @selected((string) old('fonction_id') === (string) $fonction->id)>{{ $fonction->designation }}</option>@endforeach</select>@error('fonction_id')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Poste occupé par l’utilisateur.</div></div>
                    <div class="alert alert-light border small mb-0"><i class="bi bi-shield-lock text-primary me-2"></i>Un mot de passe sécurisé sera généré automatiquement.</div>
                </div></div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('profil.index') }}" class="btn btn-light border">Annuler</a><button type="submit" class="btn btn-primary px-4"><i class="bi bi-person-check me-2"></i>Créer l’utilisateur</button></div>
    </form>
</div>
@endsection
