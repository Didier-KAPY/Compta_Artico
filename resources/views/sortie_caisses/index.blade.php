@extends('layouts.app')

@section('content')

<div class="container py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        <h4 class="mb-0">
            <i class="bi bi-cash-coin me-2"></i>
            Sorties de Caisse
        </h4>

        <a href="#" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Nouvelle sortie
        </a>

    </div>

    <!-- FILTRES -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">

            <form method="GET" action="{{ route('sortie-caisses.index') }}">

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">
                            Recherche par numéro
                        </label>

                        <input type="text"
                            name="numero"
                            class="form-control"
                            value="{{ request('numero') }}"
                            placeholder="Ex : BSC-2026-001">
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
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                    </div>

                </div>

                <div class="mt-3">
                    <a href="{{ route('sortie-caisses.index') }}"
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
                            <th>Bénéficiaire</th>
                            <th>Motif</th>
                            <th class="text-end">Montant</th>
                            <th>Monnaie</th>
                            <th>Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($sorties as $sortie)

                        <tr>

                            <td><strong>{{ $sortie->numero }}</strong></td>

                            <td>{{ \Carbon\Carbon::parse($sortie->date)->format('d/m/Y') }}</td>

                            <td>{{ $sortie->beneficiaire }}</td>

                            <td>{{ $sortie->motif }}</td>

                            <td class="text-end fw-bold">
                                {{ number_format($sortie->montant, 2) }}
                            </td>

                            <td>
                                <span class="badge bg-secondary">
                                    {{ $sortie->monnaie }}
                                </span>
                            </td>

                            <td>
                                @if($sortie->statut == 'Validé')
                                    <span class="badge bg-success">Validé</span>
                                @elseif($sortie->statut == 'Rejeté')
                                    <span class="badge bg-danger">Rejeté</span>
                                @else
                                    <span class="badge bg-warning text-dark">En attente</span>
                                @endif
                            </td>

                            <!-- ACTIONS -->
                            <td class="text-center">

                                <div class="dropdown">

                                    <button class="btn btn-sm btn-light border dropdown-toggle"
                                            data-bs-toggle="dropdown">
                                        Actions
                                    </button>

                                    <ul class="dropdown-menu">

                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('sortie-caisses.show', $sortie->id) }}">
                                                👁 Voir
                                            </a>
                                        </li>


                                    </ul>
                                </div>

                            </td>

                        </tr>

                        <!-- ================= MODAL DELETE ================= -->
                        <div class="modal fade" id="deleteModal{{ $sortie->id }}" tabindex="-1">

                            <div class="modal-dialog modal-dialog-centered">

                                <div class="modal-content">

                                    <!-- HEADER -->
                                    <div class="modal-header bg-dark text-white">
                                        <h5 class="modal-title">Confirmation suppression</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>

                                    <!-- BODY -->
                                    <div class="modal-body">
                                        Voulez-vous vraiment supprimer cette sortie de caisse ?
                                        <br>
                                        <strong>Cette action est irréversible.</strong>
                                    </div>

                                </div>

                            </div>

                        </div>

                        @empty

                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Aucune sortie de caisse trouvée
                            </td>
                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-center">
                {{ $sorties->links() }}
            </div>
        </div>
    </div>

</div>

@endsection