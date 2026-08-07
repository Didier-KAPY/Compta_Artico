@extends('layouts.app')
@section('content')
<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between">
        <div>
        <i class="bi bi-book me-2"></i>
        <strong>
            Grand Livre
        </strong>
        </div>
        @include('partials.period-export-buttons', [
            'rapport' => 'grand-livre',
            'exportParams' => array_merge(request()->query(), [
                'date_debut' => $dateDebut->toDateString(),
                'date_fin' => $dateFin->toDateString(),
            ]),
        ])
    </div>
    <div class="card-body">
        {{-- FILTRE --}}
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-bold">
                        Compte
                    </label>
                    <select name="liste_des_comptes_id"
                            class="form-select"
                            required>
                        <option value="">
                            Sélectionner un compte
                        </option>
                        @foreach($comptes as $compte)
                            <option value="{{ $compte->id }}"
                            {{ (string) ($compteSelectionne?->id ?? request('liste_des_comptes_id')) === (string) $compte->id ? 'selected' : '' }}
                        >
                                {{ $compte->compte }}
                                -
                                {{ $compte->designation }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Période (mois)
                    </label>
                    <input type="month"
                           name="mois"
                           value="{{ $moisSelectionne->format('Y-m') }}"
                           class="form-control">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                   <button class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </form>
        @if($compteSelectionne)
        <div class="alert alert-light border">
            <h5 class="fw-bold mb-1">
                Grand Livre :
                {{ $compteSelectionne->compte }}
            </h5>
            <p class="mb-0 text-muted">
                {{ $compteSelectionne->designation }}
            </p>
        </div>
        @endif
        <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover align-middle">
                <thead class="table-dark">
                    <tr class="text-center">
                        <th rowspan="2">Date</th>
                        <th rowspan="2">Compte</th>
                        <th rowspan="2">Libellé de l'écriture</th>
                        <th colspan="2">Solde initial</th>
                        <th colspan="2">Mouvements</th>
                        <th colspan="2">Solde final</th>
                    </tr>
                    <tr class="text-center">
                        <th>Débiteur</th>
                        <th>Créditeur</th>
                        <th>Débit</th>
                        <th>Crédit</th>
                        <th>Débiteur</th>
                        <th>Créditeur</th>
                    </tr>
                </thead>
                <tbody>
                @if($compteSelectionne && $ecritures->currentPage() === 1)
                    <tr class="table-primary fw-semibold">
                        <td class="text-center">{{ $dateDebut->format('d/m/Y') }}</td>
                        <td colspan="2">Situation initiale (mouvements antérieurs à la période)</td>
                        <td class="text-end">{{ number_format($resume['initial_debit'],2,',',' ') }}</td>
                        <td class="text-end">{{ number_format($resume['initial_credit'],2,',',' ') }}</td>
                        <td class="text-end">0,00</td>
                        <td class="text-end">0,00</td>
                        <td class="text-end">{{ number_format($resume['initial_debit'],2,',',' ') }}</td>
                        <td class="text-end">{{ number_format($resume['initial_credit'],2,',',' ') }}</td>
                    </tr>
                @endif
                @forelse($ecritures as $ecriture)
                   @php
                        $debit = $ecriture->debit_cdf ?? 0;
                        $credit = $ecriture->credit_cdf ?? 0;
                    @endphp
                    <tr>
                        <td class="text-center">
                            {{ $ecriture->date->format('d/m/Y') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary">
                                {{ $ecriture->compte->compte ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $ecriture->libelle ?: '-' }}</td>
                        <td class="text-end text-muted">—</td>
                        <td class="text-end text-muted">—</td>
                        <td class="text-end text-success fw-bold">{{ number_format($debit,2,',',' ') }}</td>
                        <td class="text-end text-danger fw-bold">{{ number_format($credit,2,',',' ') }}</td>
                        <td class="text-end fw-semibold">{{ number_format($ecriture->solde_debiteur,2,',',' ') }}</td>
                        <td class="text-end fw-semibold">{{ number_format($ecriture->solde_crediteur,2,',',' ') }}</td>
                    </tr>
                @empty
                    @if(!$compteSelectionne)
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-info-circle me-2"></i>
                            Sélectionnez un compte pour afficher le Grand Livre
                        </td>
                    </tr>
                    @else
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-info-circle me-2"></i>
                            Aucun mouvement sur la période ; la situation initiale reste reportée ci-dessus.
                        </td>
                    </tr>
                    @endif
                @endforelse
                @if($compteSelectionne && (!$ecritures->hasMorePages() || $ecritures->currentPage() === $ecritures->lastPage()))
                    <tr class="table-success fw-bold">
                        <td class="text-center">{{ $dateFin->format('d/m/Y') }}</td>
                        <td colspan="2">Solde final de la période</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">{{ number_format($resume['final_debit'],2,',',' ') }}</td>
                        <td class="text-end">{{ number_format($resume['final_credit'],2,',',' ') }}</td>
                    </tr>
                @endif
                </tbody>

                @if($compteSelectionne)
                <tfoot class="table-dark text-white">
                    <tr>
                        <td colspan="3" class="text-end fw-bold">TOTAUX</td>
                        <td class="text-end">{{ number_format($resume['initial_debit'],2,',',' ') }}</td>
                        <td class="text-end">{{ number_format($resume['initial_credit'],2,',',' ') }}</td>
                        <td class="text-end">{{ number_format($resume['mouvement_debit'],2,',',' ') }}</td>
                        <td class="text-end">{{ number_format($resume['mouvement_credit'],2,',',' ') }}</td>
                        <td class="text-end">{{ number_format($resume['final_debit'],2,',',' ') }}</td>
                        <td class="text-end">{{ number_format($resume['final_credit'],2,',',' ') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        {{ $ecritures->links() }}

        <button onclick="window.print()" class="btn btn-secondary mt-3">
            <i class="bi bi-printer"></i>
            Imprimer
        </button>
    </div>
</div>

<style>
.table th {
    text-align: center;
    vertical-align: middle;
}

.table td {
    white-space: nowrap;
}

@media print {
    form,
    button,
    .card-header,
    .pagination {
        display: none !important;
    }
}
</style>
@endsection
