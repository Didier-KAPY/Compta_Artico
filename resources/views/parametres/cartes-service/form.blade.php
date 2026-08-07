@extends('layouts.app')

@section('title', $carteService ? 'Modifier la carte' : 'Nouvelle carte de service')

@section('content')
<div class="container py-4" style="max-width:960px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h2 class="fw-bold mb-1">{{ $carteService ? 'Modifier la carte' : 'Créer une carte de service' }}</h2><p class="text-muted mb-0">Les nom, prénom, photo, département et fonction viennent du compte utilisateur.</p></div>
        <a href="{{ route('parametres.cartes-service.index') }}" class="btn btn-outline-secondary">Retour</a>
    </div>
    <div class="card border-0 shadow-sm"><div class="card-body p-4 p-lg-5">
        <form method="POST" action="{{ $carteService ? route('parametres.cartes-service.update', $carteService) : route('parametres.cartes-service.store') }}">
            @csrf @if($carteService) @method('PUT') @endif
            <div class="row g-4">
                <div class="col-12"><label class="form-label fw-semibold">Agent *</label><select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required><option value="">Choisir l’agent</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected(old('user_id', $carteService?->user_id)==$agent->id)>{{ $agent->nom }} {{ $agent->prenom }} — {{ $agent->departement?->designation ?? 'Sans département' }} / {{ $agent->fonction?->designation ?? 'Sans fonction' }}</option>@endforeach</select>@error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label fw-semibold">Postnom</label><input name="postnom" value="{{ old('postnom', $carteService?->postnom) }}" class="form-control @error('postnom') is-invalid @enderror">@error('postnom')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label fw-semibold">Sexe</label><select name="sexe" class="form-select @error('sexe') is-invalid @enderror"><option value="">Choisir</option><option @selected(old('sexe', $carteService?->sexe)==='Masculin')>Masculin</option><option @selected(old('sexe', $carteService?->sexe)==='Féminin')>Féminin</option></select>@error('sexe')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label fw-semibold">Date de naissance</label><input type="date" name="date_naissance" value="{{ old('date_naissance', $carteService?->date_naissance?->format('Y-m-d')) }}" class="form-control @error('date_naissance') is-invalid @enderror">@error('date_naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label fw-semibold">Date de délivrance *</label><input type="date" name="date_delivrance" required value="{{ old('date_delivrance', $carteService?->date_delivrance?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="form-control @error('date_delivrance') is-invalid @enderror">@error('date_delivrance')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label fw-semibold">Adresse</label><input name="adresse" value="{{ old('adresse', $carteService?->adresse) }}" placeholder="Laisser vide si l’adresse du compte utilisateur convient" class="form-control @error('adresse') is-invalid @enderror">@error('adresse')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label fw-semibold">Nom du gérant signataire *</label><input name="nom_signataire" required value="{{ old('nom_signataire', $carteService?->nom_signataire ?? $signataire) }}" class="form-control @error('nom_signataire') is-invalid @enderror">@error('nom_signataire')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Le nom est conservé sur la carte même si le gérant change ultérieurement.</div></div>
                <div class="col-12 d-flex justify-content-end gap-2"><a href="{{ route('parametres.cartes-service.index') }}" class="btn btn-light border">Annuler</a><button class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>{{ $carteService ? 'Enregistrer' : 'Créer la carte' }}</button></div>
            </div>
        </form>
    </div></div>
</div>
@endsection
