@extends('layouts.app')
@section('content')
<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-dark text-white d-flex align-items-center">
        <i class="bi bi-book me-2"></i>
        <strong>
            Grand Livre
        </strong>
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
                            {{ request('liste_des_comptes_id') == $compte->id ? 'selected' : '' }}
                        >
                                {{ $compte->compte }}
                                -
                                {{ $compte->designation }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        Date début
                    </label>
                    <input type="date"
                           name="date_debut"
                           value="{{ request('date_debut') }}"
                           class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        Date fin
                    </label>
                    <input type="date"
                           name="date_fin"
                           value="{{ request('date_fin') }}"
                           class="form-control">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                   <button class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </form>
        @if($ecritures->count())
        <div class="alert alert-light border">
            <h5 class="fw-bold mb-1">
                Grand Livre :
                {{ $ecritures->first()->compte->compte ?? '-' }}
            </h5>
            <p class="mb-0 text-muted">
                {{ $ecritures->first()->compte->designation ?? '-' }}
            </p>
        </div>
        @endif
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-secondary">
                    <tr class="text-center">
                        <th>Date</th>
                        <th>Compte</th>
                        <th>Désignation</th>
                        <th>Débit CDF</th>
                        <th>Crédit CDF</th>
                        <th>Solde CDF</th>
                    </tr>
                </thead>
                <tbody>
                @php
                    $solde = 0;
                    $totalDebit = 0;
                    $totalCredit = 0;
                @endphp
                @forelse($ecritures as $ecriture)
                   @php
                        $debit = $ecriture->debit_cdf ?? 0;
                        $credit = $ecriture->credit_cdf ?? 0;
                        $nature = strtolower(trim($ecriture->compte->nature ?? ''));
                        if (in_array($nature, ['actif', 'charge'])) {
                            $solde += ($debit - $credit);
                        } elseif (in_array($nature, ['passif', 'produit'])) {
                            $solde += ($credit - $debit);
                        } else {
                            // Par défaut
                            $solde += ($debit - $credit);
                        }

                        $totalDebit += $debit;
                        $totalCredit += $credit;
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
                        <td>{{ $ecriture->compte->designation ?? '-' }}</td>
                        <td class="text-end text-success fw-bold">{{ number_format($debit,2,',',' ') }}</td>
                        <td class="text-end text-danger fw-bold">{{ number_format($credit,2,',',' ') }}</td>
                        <td class="text-end fw-bold">{{ number_format($solde,2,',',' ') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"class="text-center text-muted py-4">
                            <i class="bi bi-info-circle me-2"></i>
                            Sélectionnez un compte pour afficher le Grand Livre
                        </td>
                    </tr>
                @endforelse
                </tbody>

                @if($ecritures->count())
                <tfoot class="table-dark text-white">
                    <tr>
                        <td colspan="3"class="text-end fw-bold">TOTAL</td>
                        <td class="text-end">{{ number_format($totalDebit,2,',',' ') }}</td>
                        <td class="text-end">{{ number_format($totalCredit,2,',',' ') }}</td>
                        <td class="text-end">{{ number_format($solde,2,',',' ') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection