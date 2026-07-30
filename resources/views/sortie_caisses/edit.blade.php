@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 960px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><span class="text-primary small fw-bold text-uppercase">Trésorerie</span><h3 class="mb-0">Modifier {{ $sortie->numero }}</h3></div>
        <a href="{{ route('sortie-caisses.show', $sortie->id) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
    </div>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('sortie-caisses.update', $sortie->id) }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil-square me-2"></i>Informations du bon de sortie</div>
            <div class="card-body">@include('sortie_caisses._form', ['sortie' => $sortie])</div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="{{ route('sortie-caisses.show', $sortie->id) }}" class="btn btn-outline-secondary">Annuler</a>
                <button class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Enregistrer les modifications</button>
            </div>
        </div>
    </form>
</div>
@endsection
