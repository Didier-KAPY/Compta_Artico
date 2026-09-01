@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
<style>
    .profile-cover { height: 150px; background: linear-gradient(135deg, #102a43, #1769aa); border-radius: 18px 18px 0 0; }
    .profile-avatar { width: 128px; height: 128px; object-fit: cover; border: 5px solid #fff; background: #fff; }
    .profile-initials { width: 128px; height: 128px; border: 5px solid #fff; background: #e8f1fa; color: #1769aa; font-size: 2.2rem; }
    .info-icon { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; }
</style>

<div class="container-fluid py-4 px-lg-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div><h3 class="fw-bold mb-1"><i class="bi bi-person-circle text-primary me-2"></i>Mon profil</h3><p class="text-muted mb-0">Consultez et actualisez vos informations personnelles.</p></div>
        @if(auth()->user()->hasRole(['Super Admin', 'Admin']))
            <a href="{{ route('profil.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-2"></i>Nouvel utilisateur</a>
        @endif
    </div>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><div class="fw-bold mb-1">Certaines informations doivent être corrigées :</div><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="profile-cover"></div>
                <div class="card-body text-center px-4 pb-4" style="margin-top:-68px">
                    @if($user->photo)
                        <img src="{{ asset('storage/'.$user->photo) }}" alt="Photo de {{ $user->prenom }}" class="profile-avatar rounded-circle shadow-sm">
                    @else
                        <div class="profile-initials rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center fw-bold">{{ mb_strtoupper(mb_substr($user->prenom ?? '', 0, 1).mb_substr($user->nom ?? '', 0, 1)) }}</div>
                    @endif
                    <h4 class="fw-bold mt-3 mb-1">{{ $user->prenom }} {{ $user->nom }}</h4>
                    <p class="text-muted mb-2">{{ $user->fonction?->designation ?? 'Fonction non affectée' }}</p>
                    <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-2">{{ $user->role?->designation ?? 'Aucun rôle' }}</span>
                    <hr class="my-4">
                    <div class="text-start d-grid gap-3">
                        <div class="d-flex align-items-center gap-3"><span class="info-icon bg-info bg-opacity-10 text-info"><i class="bi bi-building"></i></span><div><small class="text-muted d-block">Département</small><span class="fw-semibold">{{ $user->departement?->designation ?? 'Non affecté' }}</span></div></div>
                        <div class="d-flex align-items-center gap-3"><span class="info-icon bg-success bg-opacity-10 text-success"><i class="bi bi-telephone"></i></span><div><small class="text-muted d-block">Téléphone</small><span class="fw-semibold">{{ $user->telephone ?: 'Non renseigné' }}</span></div></div>
                        <div class="d-flex align-items-center gap-3"><span class="info-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-geo-alt"></i></span><div><small class="text-muted d-block">Adresse</small><span class="fw-semibold">{{ $user->adresse ?: 'Non renseignée' }}</span></div></div>
                    </div>
                    <div class="mt-4 p-3 rounded-3 bg-light d-flex justify-content-between align-items-center"><span class="text-muted">Statut du compte</span><span class="badge bg-{{ $user->statut === 'Actif' ? 'success' : 'danger' }}">{{ $user->statut }}</span></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <form action="{{ route('profil.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white py-3"><h5 class="fw-bold mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Informations personnelles</h5></div><div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label><input type="text" name="nom" value="{{ old('nom', $user->nom) }}" class="form-control @error('nom') is-invalid @enderror" required>@error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label><input type="text" name="prenom" value="{{ old('prenom', $user->prenom) }}" class="form-control @error('prenom') is-invalid @enderror" required>@error('prenom')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Adresse e-mail <span class="text-danger">*</span></label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Téléphone</label><input type="text" name="telephone" value="{{ old('telephone', $user->telephone) }}" class="form-control @error('telephone') is-invalid @enderror">@error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><label class="form-label fw-semibold">Adresse physique</label><input type="text" name="adresse" value="{{ old('adresse', $user->adresse) }}" class="form-control @error('adresse') is-invalid @enderror">@error('adresse')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Département</label><input class="form-control bg-light" value="{{ $user->departement?->designation ?? 'Non affecté' }}" readonly></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Fonction</label><input class="form-control bg-light" value="{{ $user->fonction?->designation ?? 'Non affectée' }}" readonly></div>
                        <div class="col-12"><label class="form-label fw-semibold">Nouvelle photo</label><input type="file" name="photo" accept="image/*" class="form-control @error('photo') is-invalid @enderror">@error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">JPG, PNG ou WEBP — taille maximale de 2 Mo.</div></div>
                        <div class="col-12"><label class="form-label fw-semibold">Signature</label>@if($user->signature)<div class="mb-2"><img src="{{ asset('storage/'.$user->signature) }}" alt="Signature de {{ $user->prenom }}" style="max-width:220px;max-height:90px;object-fit:contain"></div>@endif<input type="file" name="signature" accept="image/png" class="form-control @error('signature') is-invalid @enderror">@error('signature')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">PNG avec fond transparent, 2 Mo maximum.</div></div>
                        @if($user->hasRole(['Super Admin', 'Admin', 'Gérant', 'Gerant', 'Directeur Général']))
                            <div class="col-12">
                                <label class="form-label fw-semibold">Logo de l’entreprise</label>
                                @if($entreprise?->logo)
                                    <div class="mb-2"><img src="{{ asset('storage/'.$entreprise->logo) }}" alt="Logo de l’entreprise" style="max-width:180px;max-height:120px;object-fit:contain"></div>
                                @endif
                                <input type="file" name="logo" accept="image/png,image/jpeg,.png,.jpg,.jpeg" class="form-control @error('logo') is-invalid @enderror" @disabled(!$entreprise)>
                                @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ $entreprise ? 'Ce logo sera repris automatiquement sur les cartes de service. PNG, JPG ou JPEG, 2 Mo maximum.' : 'Veuillez d’abord renseigner l’entreprise dans les paramètres.' }}</div>
                            </div>
                        @endif
                    </div>
                </div></div>

                <div class="card border-0 shadow-sm"><div class="card-header bg-white py-3"><h5 class="fw-bold mb-0"><i class="bi bi-shield-lock text-primary me-2"></i>Sécurité</h5></div><div class="card-body p-4">
                    <p class="text-muted small">Laissez ces champs vides pour conserver votre mot de passe actuel.</p>
                    <div class="row g-3"><div class="col-md-6"><label class="form-label fw-semibold">Nouveau mot de passe</label><input type="password" name="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-6"><label class="form-label fw-semibold">Confirmer le mot de passe</label><input type="password" name="password_confirmation" autocomplete="new-password" class="form-control"></div></div>
                </div></div>

                <div class="text-end mt-4"><button class="btn btn-primary px-4"><i class="bi bi-check2-circle me-2"></i>Enregistrer les modifications</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
