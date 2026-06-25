@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <!-- CARD PRINCIPALE -->
    <div class="card border-0 rounded-4">

        <!-- HEADER -->
        <div class="card-header d-flex justify-content-between align-items-center"
             style="background: linear-gradient(90deg,#1C2A44,#243b55); color:white;">

            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-journal-text fs-4"></i>
                <h5 class="mb-0 fw-bold">Journal Caisse</h5>
            </div>

            <span class="badge bg-light text-dark">
                <i class="bi bi-collection me-1"></i>
                {{ $journaux->total() }} enregistrements
            </span>

        </div>

        <!-- FILTRE -->
        <div class="card-body">

            <form method="GET" class="row g-2 align-items-end">

                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted">Du</label>
                    <input type="date" name="from" value="{{ $from }}"
                           class="form-control form-control-sm">
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted">Au</label>
                    <input type="date" name="to" value="{{ $to }}"
                           class="form-control form-control-sm">
                </div>

                <div class="col-6 col-md-3">
                    <button class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filtrer
                    </button>
                </div>

                <div class="col-6 col-md-3">
                    <a href="{{ route('tresorerie.journal-caisse') }}"
                       class="btn btn-sm btn-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>

            </form>

        </div>

        <!-- TABLE -->
        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead style="background-color:#1C2A44; color:white;">
                        <tr>
                            <th>#</th>
                            <th>Numéro</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Entrée CDF</th>
                            <th>Sortie CDF</th>
                            <th>Entrée USD</th>
                            <th>Sortie USD</th>
                            <th>Total Entrée</th>
                            <th>Total Sortie</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($journaux as $journal)

                            <tr class="table-row">

                                <td class="text-dark">
                                    {{ $loop->iteration }}
                                </td>

                                <td>
                                    <span class="badge bg-dark px-3 py-2 fw-normal">
                                        {{ $journal->numero }}
                                    </span>
                                </td>

                                <td class="text-dark">
                                    {{ \Carbon\Carbon::parse($journal->date)->format('d/m/Y') }}
                                </td>

                                <td class="text-dark">{{ $journal->description }}</td>

                                <td class="text-dark">
                                    {{ number_format($journal->entrees_cdf, 2, ',', ' ') }}
                                </td>

                                <td class="text-dark">
                                    {{ number_format($journal->sorties_cdf, 2, ',', ' ') }}
                                </td>

                                <td class="text-dark">
                                    {{ number_format($journal->entrees_usd, 2, ',', ' ') }}
                                </td>

                                <td class="text-dark">
                                    {{ number_format($journal->sorties_usd, 2, ',', ' ') }}
                                </td>

                                <td class=" text-dark">
                                    {{ number_format($journal->total_entree_cdf, 2, ',', ' ') }}
                                </td>

                                <td class=" text-dark">
                                    {{ number_format($journal->total_sortie_cdf, 2, ',', ' ') }}
                                </td>

                                <!-- ACTIONS -->
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">

                                        <button class="btn btn-sm btn-dark btn-anim">
                                            <i class="bi bi-printer"></i>
                                        </button>

                                        <button class="btn btn-sm btn-primary btn-anim">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <button class="btn btn-sm btn-danger btn-anim">
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    </div>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    Aucun journal trouvé
                                </td>
                            </tr>

                        @endforelse

                        <!-- TOTAL -->
                        <tr style="background:#f1f3f5;font-weight:600;color:#000;">

                        <!-- 6 premières colonnes (infos texte) -->
                        <td colspan="4" class="text-end">
                            <i class="bi bi-calculator me-1"></i> TOTAUX
                        </td>

                        <!-- valeurs numériques -->
                        <td>{{ number_format($totalEntreeCdf, 2, ',', ' ') }}</td>
                        <td>{{ number_format($totalSortieCdf, 2, ',', ' ') }}</td>
                        <td>{{ number_format($totalEntreeUsd, 2, ',', ' ') }}</td>
                        <td>{{ number_format($totalSortieUsd, 2, ',', ' ') }}</td>
                        <td>{{ number_format($totalEntree, 2, ',', ' ') }}</td>
                        <td>{{ number_format($totalSortie, 2, ',', ' ') }}</td>

                        <!-- colonne actions vide -->
                        <td></td>

                    </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- SOLDE CARDS -->
    <div class="row mt-4 g-2">

        <div class="col-6 col-md-4">
            <div class="p-3 border rounded-3 text-center bg-white solde-card">
                <i class="bi bi-cash-coin fs-3 text-primary"></i>
                <div class="text-muted small mt-1">Solde CDF</div>
                <div class="fs-6 fw-bold text-dark">
                    {{ number_format($soldeCdf, 2, ',', ' ') }}
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="p-3 border rounded-3 text-center bg-white solde-card">
                <i class="bi bi-currency-dollar fs-3 text-success"></i>
                <div class="text-muted small mt-1">Solde USD</div>
                <div class="fs-6 fw-bold text-dark">
                    {{ number_format($soldeUsd, 2, ',', ' ') }}
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="p-3 border rounded-3 text-center bg-white solde-card">
                <i class="bi bi-calculator fs-3 text-dark"></i>
                <div class="text-muted small mt-1">Solde Total</div>
                <div class="fs-6 fw-bold text-dark">
                    {{ number_format($soldeTotal, 2, ',', ' ') }}
                </div>
            </div>
        </div>

    </div>

</div>

<!-- STYLE CLEAN -->
<style>

.table-row {
    transition: background-color 0.15s ease-in-out;
}

.table-row:hover {
    background-color: #f8f9fa;
}

.btn-anim {
    transition: transform 0.15s ease-in-out;
}

.btn-anim:hover {
    transform: scale(1.06);
}

.solde-card {
    transition: transform 0.2s ease-in-out;
}

.solde-card:hover {
    transform: translateY(-3px);
}

</style>

@endsection