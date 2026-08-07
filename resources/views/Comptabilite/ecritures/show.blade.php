@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><span class="text-uppercase text-muted small fw-bold">Écriture comptable</span><h3 class="mb-0">{{ $ecriture->piece ?: 'Écriture #'.$ecriture->id }}</h3></div>
        <div class="d-flex gap-2">@can('deleteFinancialDocument')<button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalSuppressionDocument"><i class="bi bi-trash me-1"></i>Supprimer</button>@endcan<a href="{{ route('ecritures.liste') }}" class="btn btn-secondary">Retour</a></div>
    </div>
    @include('partials.document-navigation')
    @include('partials.financial-delete-modal', ['documentType'=>'Écriture comptable','documentReference'=>$ecriture->piece,'documentStatus'=>$ecriture->statut,'deleteRoute'=>route('ecritures.destroy',$ecriture)])
    <div class="card border-0 shadow-sm"><div class="card-body"><dl class="row mb-0">
        <dt class="col-sm-3">Date</dt><dd class="col-sm-9">{{ $ecriture->date?->format('d/m/Y') }}</dd>
        <dt class="col-sm-3">Journal</dt><dd class="col-sm-9">{{ $ecriture->journal?->reference ?? '—' }}</dd>
        <dt class="col-sm-3">Compte</dt><dd class="col-sm-9">{{ $ecriture->compte?->compte }} — {{ $ecriture->compte?->designation }}</dd>
        <dt class="col-sm-3">Libellé</dt><dd class="col-sm-9">{{ $ecriture->libelle }}</dd>
        <dt class="col-sm-3">Débit CDF</dt><dd class="col-sm-9">{{ number_format($ecriture->debit_cdf,2,',',' ') }}</dd>
        <dt class="col-sm-3">Crédit CDF</dt><dd class="col-sm-9">{{ number_format($ecriture->credit_cdf,2,',',' ') }}</dd>
        <dt class="col-sm-3">Statut</dt><dd class="col-sm-9"><span class="badge bg-secondary">{{ $ecriture->statut }}</span></dd>
    </dl></div></div>
</div>
@endsection
