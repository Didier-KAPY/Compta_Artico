@extends('layouts.app')
@section('title', 'Nouvelle opération diverse')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-journal-plus me-2"></i>Nouvelle opération diverse</h4>
        <a href="{{ route('comptabilite.imputation-compte') }}" class="btn btn-outline-secondary">Retour au journal</a>
    </div>
    @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="alert alert-info">Cette saisie crée des écritures comptables équilibrées en CDF. Elle ne génère aucun BRC et aucun mouvement de caisse.</div>
    <form method="POST" action="{{ route('comptabilite.imputation-compte.store') }}" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label" for="date">Date</label><input class="form-control" type="date" id="date" name="date" value="{{ old('date', today()->toDateString()) }}" required></div>
                <div class="col-md-4"><label class="form-label" for="journal_type_id">Journal comptable</label><select class="form-select" id="journal_type_id" name="journal_type_id" required><option value="">Choisir</option>@foreach($journaux as $journal)<option value="{{ $journal->id }}" @selected((string) old('journal_type_id') === (string) $journal->id)>{{ $journal->code }} — {{ $journal->libelle }}</option>@endforeach</select></div>
                <div class="col-md-5"><label class="form-label" for="description">Description de l’opération</label><input class="form-control" id="description" name="description" value="{{ old('description') }}" maxlength="255" required></div>
            </div>
            <div class="table-responsive"><table class="table table-bordered align-middle"><thead class="table-light"><tr><th>Compte</th><th>Libellé</th><th>Sens</th><th>Montant (CDF)</th><th></th></tr></thead><tbody id="operation-lines">
                @foreach(old('lignes', [['compte_id'=>'','libelle'=>'','sens'=>'debit','montant'=>''], ['compte_id'=>'','libelle'=>'','sens'=>'credit','montant'=>'']]) as $i => $ligne)
                    <tr><td><select class="form-select" name="lignes[{{ $i }}][compte_id]" required><option value="">Choisir un compte</option>@foreach($comptes as $compte)<option value="{{ $compte->id }}" @selected((string) ($ligne['compte_id'] ?? '') === (string) $compte->id)>{{ $compte->compte }} — {{ $compte->designation }}</option>@endforeach</select></td><td><input class="form-control" name="lignes[{{ $i }}][libelle]" value="{{ $ligne['libelle'] ?? '' }}" required maxlength="255"></td><td><select class="form-select" name="lignes[{{ $i }}][sens]" required><option value="debit" @selected(($ligne['sens'] ?? '') === 'debit')>Débit</option><option value="credit" @selected(($ligne['sens'] ?? '') === 'credit')>Crédit</option></select></td><td><input class="form-control" type="number" step="0.01" min="0.01" name="lignes[{{ $i }}][montant]" value="{{ $ligne['montant'] ?? '' }}" required></td><td><button type="button" class="btn btn-outline-danger remove-line" aria-label="Supprimer la ligne"><i class="bi bi-trash"></i></button></td></tr>
                @endforeach
            </tbody></table></div>
            <button type="button" id="add-line" class="btn btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Ajouter une ligne</button>
        </div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer l’opération</button></div>
    </form>
</div>
<template id="operation-line-template"><tr><td><select class="form-select" data-name="compte_id" required><option value="">Choisir un compte</option>@foreach($comptes as $compte)<option value="{{ $compte->id }}">{{ $compte->compte }} — {{ $compte->designation }}</option>@endforeach</select></td><td><input class="form-control" data-name="libelle" required maxlength="255"></td><td><select class="form-select" data-name="sens" required><option value="debit">Débit</option><option value="credit">Crédit</option></select></td><td><input class="form-control" type="number" step="0.01" min="0.01" data-name="montant" required></td><td><button type="button" class="btn btn-outline-danger remove-line" aria-label="Supprimer la ligne"><i class="bi bi-trash"></i></button></td></tr></template>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('operation-lines');
    const template = document.getElementById('operation-line-template');
    document.getElementById('add-line').addEventListener('click', () => {
        const index = body.querySelectorAll('tr').length;
        const row = template.content.cloneNode(true);
        row.querySelectorAll('[data-name]').forEach(input => input.name = `lignes[${index}][${input.dataset.name}]`);
        body.appendChild(row);
    });
    body.addEventListener('click', event => {
        if (event.target.closest('.remove-line') && body.querySelectorAll('tr').length > 2) event.target.closest('tr').remove();
    });
});
</script>
@endpush
@endsection
