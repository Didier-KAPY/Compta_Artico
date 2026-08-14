@extends('layouts.app')

@section('title', 'Cartes de service')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <a href="{{ route('parametres.parametre') }}" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Paramètres</a>
            <h2 class="fw-bold mb-1 mt-2">Cartes de service</h2>
            <p class="text-muted mb-0">Cartes professionnelles au format PVC portrait.</p>
        </div>
        <a href="{{ route('parametres.cartes-service.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Créer une carte</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card border-0 shadow-sm">
        <div class="card-body border-bottom">
            <form class="row g-2" method="GET">
                <div class="col-md-6"><input name="recherche" value="{{ $recherche }}" class="form-control" placeholder="Nom, prénom ou numéro de carte"></div>
                <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Rechercher</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Numéro</th><th>Agent</th><th>Département / fonction</th><th>Délivrée le</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse($cartes as $carte)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $carte->numero }}</span></td>
                        <td><strong>{{ $carte->user?->nom }} {{ $carte->postnom }} {{ $carte->user?->prenom }}</strong><small class="d-block text-muted">{{ $carte->user?->email }}</small></td>
                        <td>{{ $carte->user?->departement?->designation ?? '—' }}<small class="d-block text-muted">{{ $carte->user?->fonction?->designation ?? 'Aucune fonction' }}</small></td>
                        <td>{{ $carte->date_delivrance?->format('d/m/Y') }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('parametres.cartes-service.show', $carte) }}" title="Aperçu"><i class="bi bi-eye"></i></a>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('parametres.cartes-service.edit', $carte) }}" title="Modifier"><i class="bi bi-pencil"></i></a>
                            <a class="btn btn-sm btn-outline-danger" href="{{ route('parametres.cartes-service.pdf', $carte) }}" title="Télécharger le PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                            <form class="d-inline" method="POST" action="{{ route('parametres.cartes-service.destroy', $carte) }}" data-confirm="Supprimer cette carte ?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">Aucune carte de service enregistrée.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($cartes->hasPages())<div class="card-footer bg-white">{{ $cartes->links() }}</div>@endif
    </div>
</div>
@endsection
