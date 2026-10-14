@extends('layouts.app')
@section('title', 'Bon de transfert '.$sortie->reference)
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <a href="{{ route('journaux.transferts-internes') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
        <button type="button" onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Imprimer / PDF</button>
    </div>
    <div class="card border-dark shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="text-center border-bottom pb-3 mb-4">
                <h3 class="fw-bold mb-1">BON DE TRANSFERT INTERNE DE TRÉSORERIE</h3>
                <div class="text-muted">Référence : <strong>{{ $sortie->reference }}</strong></div>
            </div>
            <div class="row mb-4">
                <div class="col-6"><strong>Date :</strong> {{ $sortie->date?->format('d/m/Y') }}</div>
                <div class="col-6 text-end"><strong>Statut :</strong> {{ $sortie->statut }}</div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><div class="border rounded p-3 h-100"><small class="text-muted d-block">COMPTE SOURCE</small><strong>{{ $sortie->journalType?->compte?->compte }} — {{ $sortie->journalType?->compte?->designation }}</strong><div>{{ $sortie->journalType?->code }}</div></div></div>
                <div class="col-md-6"><div class="border rounded p-3 h-100"><small class="text-muted d-block">COMPTE DESTINATAIRE</small><strong>{{ $entree->journalType?->compte?->compte }} — {{ $entree->journalType?->compte?->designation }}</strong><div>{{ $entree->journalType?->code }}</div></div></div>
            </div>
            <p><strong>Motif :</strong> {{ $sortie->description }}</p>
            <div class="alert alert-light border"><strong>Montant du transfert :</strong> {{ number_format((float)$sortie->montant_ttc, 2, ',', ' ') }} {{ $sortie->monnaie }} <span class="text-muted">(monnaie d’origine)</span></div>
            <div class="d-flex justify-content-end mb-5"><span>Enregistré par : {{ trim(($sortie->user?->prenom ?? '').' '.($sortie->user?->nom ?? '')) }}</span></div>
            <div class="row text-center mt-5"><div class="col-6"><div class="border-top pt-2 mx-3">Remis par</div></div><div class="col-6"><div class="border-top pt-2 mx-3">Reçu par</div></div></div>
        </div>
    </div>
</div>
<style>@media print { body { background: #fff !important; } .container { max-width: 100% !important; padding: 0 !important; } .card { border: 0 !important; box-shadow: none !important; } }</style>
@endsection
