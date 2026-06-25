@extends('layouts.app')

@section('content')

<div class="container py-4">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3">

    <h4 class="mb-0">
        <i class="bi bi-journal-bookmark me-2"></i>
        Caisse
    </h4>

</div>

<!-- FILTRES -->
<!-- BOUTON FILTRE -->
<div class="mb-3">

    <button class="btn btn-outline-primary"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#zoneFiltre">

        <i class="bi bi-funnel"></i>
        Afficher les filtres

    </button>

</div>


<!-- FILTRES CACHÉS -->
<div class="collapse" id="zoneFiltre">

    <div class="card shadow-sm border-0 mb-3">

        <div class="card-body">

            <form method="GET" action="{{ route('journaux.index') }}">

                <div class="row g-3">


                    <div class="col-md-4">

                        <label class="form-label">
                            Référence
                        </label>

                        <input type="text"
                               name="reference"
                               class="form-control"
                               value="{{ request('reference') }}"
                               placeholder="Référence">

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

                    <a href="{{ route('journaux.index') }}"
                       class="btn btn-secondary">

                        <i class="bi bi-arrow-clockwise"></i>
                        Réinitialiser

                    </a>

                </div>


            </form>


        </div>

    </div>

</div>

<!-- TABLEAU -->
<div class="card shadow-sm border-0">

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-dark">
                    <tr>
                        <th>Référence</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Monnaie</th>
                        <th class="text-end">Entrées CDF</th>
                        <th class="text-end">Sorties CDF</th>
                        <th class="text-end">Entrées USD</th>
                        <th class="text-end">Sorties USD</th>
                        <th>Statut</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($journaux as $journal)

                    <tr>

                        <td>
                            <strong>{{ $journal->reference }}</strong>
                        </td>

                        <td>
                            {{ \Carbon\Carbon::parse($journal->date)->format('d/m/Y') }}
                        </td>

                        <td>
                            {{ $journal->description }}
                        </td>

                        <td>
                            <span class="badge bg-secondary">
                                {{ $journal->monnaie }}
                            </span>
                        </td>

                        <td class="text-end fw-bold">
                            {{ number_format($journal->entrees_cdf, 2) }}
                        </td>

                        <td class="text-end fw-bold">
                            {{ number_format($journal->sorties_cdf, 2) }}
                        </td>

                        <td class="text-end fw-bold">
                            {{ number_format($journal->entrees_usd, 2) }}
                        </td>

                        <td class="text-end fw-bold">
                            {{ number_format($journal->sorties_usd, 2) }}
                        </td>

                        <td>
                            @if($journal->statut == 'Validé')
                                <span class="badge bg-success">Validé</span>
                            @elseif($journal->statut == 'Rejeté')
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
                                        href="{{ route('journaux.show', $journal->id) }}">
                                            👁 Voir
                                        </a>
                                    </li>

                                   

                                </ul>

                            </div>

                        </td>

                    </tr>

                    @empty

                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Aucun journal trouvé
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
    {{ $journaux->links() }}
</div>
</div>

@endsection
