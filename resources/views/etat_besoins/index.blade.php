@extends('layouts.app')

@section('content')

<div class="container py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        <h4 class="mb-0">
            <i class="bi bi-file-earmark-text me-2"></i>
            États de Besoin
        </h4>

        <a href="{{ route('etat-besoins.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Nouveau
        </a>

    </div>

    <!-- FILTRES -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">

            <form method="GET" action="{{ route('etat-besoins.index') }}">

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">
                            Recherche par numéro
                        </label>

                        <input type="text"
                               name="numero"
                               class="form-control"
                               value="{{ request('numero') }}"
                               placeholder="Ex : EB-2026-001">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Date début
                        </label>

                        <input type="date"
                               name="date_debut"
                               class="form-control"
                               value="{{ request('date_debut') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Date fin
                        </label>

                        <input type="date"
                               name="date_fin"
                               class="form-control"
                               value="{{ request('date_fin') }}">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">

                        <button type="submit"
                                class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                            Filtrer
                        </button>

                    </div>

                </div>

                <div class="mt-3">

                    <a href="{{ route('etat-besoins.index') }}"
                       class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i>
                        Réinitialiser
                    </a>

                </div>

            </form>

        </div>
    </div>

    <!-- TABLE -->
    <div class="card shadow-sm border-0">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-dark">
                        <tr>
                            <th>N°</th>
                            <th>Date</th>
                            <th>Service</th>
                            <th>Demandeur</th>
                            <th>Désignation</th>
                            <th>Monnaie</th>
                            <th class="text-end">Montant</th>
                            <th>Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($etatBesoins as $etat)

                        <tr>

                            <td><strong>{{ $etat->numero }}</strong></td>

                            <td>
                                {{ \Carbon\Carbon::parse($etat->date)->format('d/m/Y') }}
                            </td>

                            <td>{{ $etat->service }}</td>

                            <td>{{ $etat->demandeur }}</td>

                            <td>
                                @if($etat->lignes->count())
                                    {{ $etat->lignes->pluck('designation')->join(', ') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>
                                <span class="badge bg-secondary">
                                    {{ $etat->monnaie }}
                                </span>
                            </td>

                            <td class="text-end fw-bold">
                                {{ number_format($etat->montant_estime, 2) }}
                            </td>

                            <td>
                                @if($etat->statut == 'Validé')
                                    <span class="badge bg-success">Validé</span>
                                @elseif($etat->statut == 'Rejeté')
                                    <span class="badge bg-danger">Rejeté</span>
                                @else
                                    <span class="badge bg-warning text-dark">En attente</span>
                                @endif
                            </td>

                            <td class="text-center">

                                <div class="dropdown">

                                    <button class="btn btn-sm btn-light border dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        Actions
                                    </button>

                                    <ul class="dropdown-menu">

                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('etat-besoins.show', $etat->id) }}">
                                                👁 Voir
                                            </a>
                                        </li>

                                        <li><hr class="dropdown-divider"></li>

                                    </ul>

                                </div>

                            </td>

                        </tr>

                        @empty

                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Aucun état de besoin trouvé
                            </td>
                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-center">
                {{ $etatBesoins->links() }}
            </div>
        </div>
    </div>

</div>

@endsection