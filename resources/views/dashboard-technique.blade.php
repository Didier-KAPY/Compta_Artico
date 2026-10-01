@extends('layouts.app')
@section('title', 'Tableau de bord technique')
@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2>Tableau de bord technique</h2>
        <p class="text-muted">Bonjour {{ $user->prenom }}. Suivi des états de besoins de toutes les directions sauf la Direction financière.</p>
        <small class="text-muted">Toutes périodes et tous statuts confondus, hors éléments supprimés.</small>
    </div>
    <div class="row g-3 mb-4">
        @foreach([
            ['Tous les états de besoins', $total, ['statut' => ''], 'primary'],
            ['En attente', $statuts['En attente'] ?? 0, ['statut' => 'En attente'], 'warning'],
            ['Validés', $statuts['Validé'] ?? 0, ['statut' => 'Validé'], 'success'],
            ['Rejetés', $statuts['Rejeté'] ?? 0, ['statut' => 'Rejeté'], 'secondary'],
            ['Sans pièce justificative', $sansPiece, ['statut' => '', 'sans_piece' => 1], 'danger'],
        ] as [$label, $nombre, $filtres, $couleur])
            <div class="col-12 col-sm-6 col-xl">
                <a href="{{ route('etat-besoins.index', $filtres) }}" class="card h-100 shadow-sm text-decoration-none border-{{ $couleur }}">
                    <div class="card-body"><span class="text-body">{{ $label }}</span><strong class="d-block fs-2 text-{{ $couleur }}">{{ number_format($nombre, 0, ',', ' ') }}</strong></div>
                </a>
            </div>
        @endforeach
    </div>
    <div class="d-flex flex-wrap gap-2 mb-4">
        @can('createEtatBesoin')<a class="btn btn-primary" href="{{ route('etat-besoins.create') }}">Nouvel état de besoins</a>@endcan
        <a class="btn btn-outline-primary" href="{{ route('journaux.index') }}">Consulter les journaux</a>
        @can('viewAttendance')<a class="btn btn-outline-primary" href="{{ route('parametres.rh.presences') }}">Présences</a>@endcan
        @can('manageAttendance')<a class="btn btn-outline-primary" href="{{ route('parametres.rh.presences.scanner') }}">Pointages</a>@endcan
        @can('manageServiceCards')<a class="btn btn-outline-primary" href="{{ route('parametres.cartes-service.index') }}">Cartes de service</a>@endcan
    </div>
    <div class="card shadow-sm">
        <div class="card-header d-flex flex-wrap justify-content-between gap-2 align-items-center">
            <div><h5 class="mb-1">États de besoins sans pièce justificative</h5><small>Les 10 plus récents, tous statuts confondus.</small></div>
            <a href="{{ route('etat-besoins.index', ['statut' => '', 'sans_piece' => 1]) }}" class="btn btn-sm btn-outline-danger">Voir tous ({{ $sansPiece }})</a>
        </div>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Numéro</th><th>Date</th><th>Direction / service</th><th>Demandeur</th><th>Statut</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($derniersSansPiece as $etat)
                    <tr><td>{{ $etat->numero }}</td><td>{{ $etat->date?->format('d/m/Y') }}</td><td>{{ $etat->departement?->designation ?? $etat->service ?? '—' }}</td><td>{{ $etat->demandeur }}</td><td>{{ $etat->statut }}</td><td>@can('consultEtatBesoin')<a class="btn btn-sm btn-outline-primary" href="{{ route('etat-besoins.show', $etat) }}">Voir</a>@endcan</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun état de besoins sans pièce justificative dans votre périmètre.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endsection
