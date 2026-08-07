@extends('layouts.app')

@section('title', 'Aperçu de la carte')

@section('content')
<div class="container py-4">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h2 class="fw-bold mb-1">Aperçu de la carte</h2><p class="text-muted mb-0">Format PVC portrait : 53,98 × 85,60 mm.</p></div>
        <div class="d-flex gap-2"><a href="{{ route('parametres.cartes-service.index') }}" class="btn btn-outline-secondary">Retour</a><a href="{{ route('parametres.cartes-service.edit', $carteService) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Modifier</a><button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Imprimer</button><a href="{{ route('parametres.cartes-service.pdf', $carteService) }}" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a></div>
    </div>
    @include('parametres.cartes-service.carte')
</div>
@endsection
