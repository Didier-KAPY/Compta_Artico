@extends('layouts.app')

@section('content')

<div class="container py-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-person-circle me-2"></i>
                Mon profil
            </h4>
            <small class="text-muted">
                Gestion de vos informations personnelles
            </small>
        </div>
        @if(auth()->user()->role?->designation == 'super admin')
            <a href="{{ route('profil.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i>
                Nouvel utilisateur
            </a>
        @endif
    </div>
    <div class="row g-4">
        {{-- PHOTO ET INFOS --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    @if($user->photo)
                        <img src="{{ asset('storage/'.$user->photo) }}"
                             class="rounded-circle shadow mb-3"
                             style="width:130px;height:130px;object-fit:cover;">
                    @else
                        <i class="bi bi-person-circle text-secondary"
                           style="font-size:130px;"></i>
                    @endif
                    <h5 class="mt-3">
                        {{ $user->prenom }}
                        {{ $user->nom }}
                    </h5>
                    <span class="badge bg-primary">
                        {{ $user->role?->designation ?? 'Aucun rôle' }}
                    </span>
                    <hr>
                    <small class="text-muted">
                        Statut :
                    </small>
                    <br>
                    @if($user->statut == 'Actif')
                        <span class="badge bg-success">
                            Actif
                        </span>
                    @else
                        <span class="badge bg-danger">
                            Inactif
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- FORMULAIRE --}}
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-pencil-square me-2"></i>
                    Modifier mes informations
                </div>
                <div class="card-body">
                    <form action="{{ route('profil.update') }}"
                          method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        <h6 class="text-primary mb-3">
                            Informations personnelles
                        </h6>
                        <div class="row">

                            {{-- NOM --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    Nom
                                </label>
                                <input type="text"
                                       name="nom"
                                       class="form-control"
                                       value="{{ old('nom',$user->nom) }}"
                                       required>
                            </div>
                            {{-- PRENOM --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    Prénom
                                </label>
                                <input type="text"
                                       name="prenom"
                                       class="form-control"
                                       value="{{ old('prenom',$user->prenom) }}"
                                       required>
                            </div>
                            {{-- EMAIL --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    Email
                                </label>
                                <input type="email"
                                       name="email"
                                       class="form-control"
                                       value="{{ old('email',$user->email) }}"
                                       required>
                            </div>
                            {{-- ROLE --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    Rôle
                                </label>
                                <select name="role_id"
                                        class="form-select"
                                        required>
                                    <option value="">
                                        -- Choisir un rôle --
                                    </option>

                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}"
                                        {{ $user->role_id == $role->id ? 'selected':'' }}>
                                            {{ $role->designation }} - {{ $role->type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <hr>

                        {{-- PHOTO --}}
                        <h6 class="text-primary mb-3">
                            Photo de profil
                        </h6>
                        <input type="file"
                               name="photo"
                               class="form-control mb-2">
                        <small class="text-muted">
                            Format image uniquement (max 2 Mo)
                        </small>
                        <hr>
                        {{-- PASSWORD --}}
                        <h6 class="text-primary mb-3">
                            Sécurité
                        </h6>
                        <label class="form-label fw-bold">
                            Nouveau mot de passe
                        </label>
                        <input type="password"
                               name="password"
                               class="form-control"
                               placeholder="Laisser vide pour conserver l'ancien">
                        <div class="text-end mt-4">
                            <button class="btn btn-success">
                                <i class="bi bi-save me-1"></i>
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection