@extends('layouts.app')

@section('content')

<div class="container py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        <h4 class="mb-0">
            <i class="bi bi-cash-coin me-2"></i>
            Entrées de Caisse
        </h4>

        <a href="{{ route('entree-caisses.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Nouveau
        </a>

    </div>

    <!-- FILTRES -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">

            <form method="GET" action="{{ route('entree-caisses.index') }}">

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Recherche par numéro</label>
                        <input type="text"
                               name="numero"
                               class="form-control"
                               value="{{ request('numero') }}"
                               placeholder="Ex: EC-2026-001">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Date début</label>
                        <input type="date"
                               name="date_debut"
                               class="form-control"
                               value="{{ request('date_debut') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Date fin</label>
                        <input type="date"
                               name="date_fin"
                               class="form-control"
                               value="{{ request('date_fin') }}">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                            Filtrer
                        </button>
                    </div>

                </div>

                <div class="mt-3">
                    <a href="{{ route('entree-caisses.index') }}"
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
                            <th>Motif</th>
                            <th class="text-end">Montant</th>
                            <th>Monnaie</th>
                            <th>Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($entrees as $entree)

                        <tr>

                            <td><strong>{{ $entree->numero }}</strong></td>

                            <td>
                                {{ \Carbon\Carbon::parse($entree->date)->format('d/m/Y') }}
                            </td>

                            <td>{{ $entree->motif }}</td>

                            <td class="text-end fw-bold">
                                {{ number_format($entree->montant, 2) }}
                            </td>

                            <td>
                                <span class="badge bg-secondary">
                                    {{ $entree->monnaie }}
                                </span>
                            </td>

                            <td>
                                @if($entree->statut == 'Validé')
                                    <span class="badge bg-success">Validé</span>
                                @elseif($entree->statut == 'Rejeté')
                                    <span class="badge bg-danger">Rejeté</span>
                                @else
                                    <span class="badge bg-warning text-dark">En attente</span>
                                @endif
                            </td>

                            <td class="text-center">

                                <div class="dropdown">

                                    <button class="btn btn-sm btn-light border dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown">
                                        Actions
                                    </button>

                                    <ul class="dropdown-menu">

                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('entree-caisses.show', $entree->id) }}">
                                                👁 Voir
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item text-warning"
                                               href="{{ route('entree-caisse.edit', $entree->id) }}">
                                                ✏ Modifier
                                            </a>
                                        </li>

                                        <li><hr class="dropdown-divider"></li>

                                        <li>
                                            <form action="{{ route('entree-caisses.destroy', $entree->id) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Voulez-vous supprimer cette entrée ?')">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                        class="dropdown-item text-danger">
                                                    🗑 Supprimer
                                                </button>

                                            </form>
                                        </li>

                                    </ul>

                                </div>

                            </td>

                        </tr>

                        @empty

                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Aucune entrée de caisse trouvée
                            </td>
                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- PAGINATION -->
    <div class="mt-3 d-flex justify-content-center">
        {{ $entrees->links() }}
    </div>

</div>

@endsection