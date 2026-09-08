@extends('layouts.app')
@section('title','QR Code - '.$employe->matricule)
@section('module-sidebar') @include('ressources_humaines._sidebar') @endsection
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 d-print-none">
        <div><h2 class="fw-bold mb-1">QR Code de pointage</h2><p class="text-muted mb-0">{{$employe->nom}} {{$employe->postnom}} {{$employe->prenom}} — {{$employe->matricule}}</p></div>
        <div class="d-flex gap-2"><a href="{{route('parametres.rh.employes.show',$employe)}}" class="btn btn-outline-secondary">Retour</a><a href="{{route('parametres.rh.employes.qr.download',$employe)}}" class="btn btn-outline-primary"><i class="bi bi-download me-1"></i>Télécharger</a><button type="button" onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Imprimer</button></div>
    </div>
    @if(session('success'))<div class="alert alert-success d-print-none">{{session('success')}}</div>@endif
    <div class="card border-0 shadow-sm mx-auto text-center qr-sheet"><div class="card-body p-4">
        <h4 class="fw-bold mb-1">QR CODE DE POINTAGE</h4><p class="text-muted">Arrivée / Départ</p>
        <img src="{{$qrCodeData}}" alt="QR Code de pointage de {{$employe->matricule}}" class="qr-image">
        <h5 class="fw-bold mt-3 mb-1">{{$employe->nom}} {{$employe->postnom}} {{$employe->prenom}}</h5><div>{{$employe->matricule}}</div><small class="text-muted">{{$employe->service?->nom ?? $employe->departement?->designation ?? '—'}}</small>
        <p class="small text-danger mt-3 mb-0">Code personnel et confidentiel. Ne pas partager.</p>
    </div></div>
    <form method="POST" action="{{route('parametres.rh.employes.qr.regenerate',$employe)}}" class="text-center mt-3 d-print-none" data-confirm="Régénérer ce QR Code ? L’ancien deviendra immédiatement inutilisable.">@csrf @method('PATCH')<button class="btn btn-outline-danger"><i class="bi bi-arrow-repeat me-1"></i>Régénérer le QR Code</button></form>
</div>
<style>.qr-sheet{max-width:440px}.qr-image{width:280px;max-width:100%;height:auto}@media print{.sidebar,.app-topbar{display:none!important}.content{margin:0!important;padding:0!important}.qr-sheet{box-shadow:none!important;border:1px solid #ddd!important;margin-top:20mm!important}}</style>
@endsection
