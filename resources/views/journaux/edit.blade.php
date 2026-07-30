@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 960px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><span class="text-primary small fw-bold text-uppercase">Comptabilité</span><h3 class="mb-0">Modifier le journal {{ $journal->reference }}</h3></div>
        <a href="{{ route('journaux.show', $journal->id) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
    </div>
    <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Pour préserver l’équilibre comptable, seuls la date, le partenaire, le libellé et la pièce peuvent être modifiés.</div>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('journaux.update', $journal->id) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-header"><i class="bi bi-journal-text me-2"></i>Informations modifiables</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Date *</label><input type="date" name="date" class="form-control" value="{{ old('date', $journal->date?->format('Y-m-d')) }}" required></div>
                    <div class="col-md-8"><label class="form-label">Nom du partenaire</label><input type="text" name="nom_partenaire" class="form-control" value="{{ old('nom_partenaire', $journal->nom_partenaire) }}"></div>
                    <div class="col-md-6"><label class="form-label">Téléphone</label><input type="text" name="telephone_partenaire" class="form-control" value="{{ old('telephone_partenaire', $journal->telephone_partenaire) }}"></div>
                    <div class="col-md-6"><label class="form-label">Adresse</label><input type="text" name="adresse_partenaire" class="form-control" value="{{ old('adresse_partenaire', $journal->adresse_partenaire) }}"></div>
                    <div class="col-12"><label class="form-label">Libellé *</label><textarea name="description" rows="4" class="form-control" required>{{ old('description', $journal->description) }}</textarea></div>
                    <div class="col-12"><label class="form-label">Nouvelle pièce justificative</label><input type="file" name="piece_justificatif" class="form-control" accept=".pdf,.jpg,.jpeg,.png"><small class="text-muted">Laissez vide pour conserver la pièce actuelle.</small></div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="{{ route('journaux.show', $journal->id) }}" class="btn btn-outline-secondary">Annuler</a>
                <button class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Enregistrer</button>
            </div>
        </div>
    </form>
</div>
@endsection
