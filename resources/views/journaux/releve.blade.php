@extends('layouts.app')

@section('content')

<div class="container-fluid py-3">

<div class="card shadow border-0">

<div class="card-header bg-dark text-white">

    <h5 class="mb-0">
        <i class="bi bi-journal-text me-2"></i>
        Relevé Journal
    </h5>

</div>

<div class="card-body">

{{-- FILTRE --}}

<form method="GET"
      action="{{ route('journaux.releve') }}"
      class="mb-4">

<div class="row g-3 align-items-end">

    <div class="col-md-3">

        <label class="form-label">
            Date début
        </label>

        <input
            type="date"
            name="date_debut"
            class="form-control"
            value="{{ request('date_debut', date('Y-m-d')) }}">

    </div>


    <div class="col-md-3">

        <label class="form-label">
            Date fin
        </label>

        <input
            type="date"
            name="date_fin"
            class="form-control"
            value="{{ request('date_fin', date('Y-m-d')) }}">

    </div>


    <div class="col-md-4">

        <label class="form-label">
            Compte de trésorerie
        </label>

        <select
            name="journal_type_id"
            class="form-select">

            <option value="">
                Tous les comptes de trésorerie
            </option>

            @foreach($comptesTresorerie as $journalType)

                <option
                    value="{{ $journalType->id }}"
                    {{ request('journal_type_id') == $journalType->id ? 'selected' : '' }}>

                    {{ $journalType->compte->compte }}
                    -
                    {{ $journalType->compte->designation }}

                </option>

            @endforeach

        </select>

    </div>


    <div class="col-md-2">

        <button
            type="submit"
            class="btn btn-primary w-100">

            <i class="bi bi-search"></i>

            Filtrer

        </button>

    </div>

</div>

</form>

    @php

    $totalEntreeCDF = 0;
    $totalSortieCDF = 0;

    $totalEntreeUSD = 0;
    $totalSortieUSD = 0;

    @endphp

    <div class="table-responsive">

        <table class="table table-bordered table-striped table-hover">

            <thead class="table-dark">

                <tr>

                    <th>Date</th>

                    <th>Compte</th>

                    <th>Désignation</th>

                    <th>Entrée CDF</th>

                    <th>Sortie CDF</th>

                    <th>Entrée USD</th>

                    <th>Sortie USD</th>

                </tr>

            </thead>

            

@forelse($journaux as $journal)

@php

$totalEntreeCDF += $journal->entrees_cdf ?? 0;
$totalSortieCDF += $journal->sorties_cdf ?? 0;

$totalEntreeUSD += $journal->entrees_usd ?? 0;
$totalSortieUSD += $journal->sorties_usd ?? 0;

@endphp

<tr>

    <td>
        {{ \Carbon\Carbon::parse($journal->date)->format('d/m/Y') }}
    </td>

    <td>

        @if($journal->journalType && $journal->journalType->compte)

            {{ $journal->journalType->compte->compte }}

        @else

            -

        @endif

    </td>

    <td>


            {{ $journal->description }}

     

    </td>

    <td class="text-end">

        {{ number_format($journal->entrees_cdf ?? 0,2,',',' ') }}

    </td>

    <td class="text-end">

        {{ number_format($journal->sorties_cdf ?? 0,2,',',' ') }}

    </td>

    <td class="text-end">

        {{ number_format($journal->entrees_usd ?? 0,2,',',' ') }}

    </td>

    <td class="text-end">

        {{ number_format($journal->sorties_usd ?? 0,2,',',' ') }}

    </td>

</tr>

@empty

<tr>

<td colspan="7" class="text-center text-muted py-4">

<i class="bi bi-inbox fs-3 d-block mb-2"></i>

Aucune opération trouvée.

</td>

</tr>

@endforelse

</tbody>

<tfoot class="table-light">

<tr class="fw-bold">

<td colspan="3" class="text-end">

TOTAL

</td>

<td class="text-end">

{{ number_format($totalEntreeCDF,2,',',' ') }}

</td>

<td class="text-end">

{{ number_format($totalSortieCDF,2,',',' ') }}

</td>

<td class="text-end">

{{ number_format($totalEntreeUSD,2,',',' ') }}

</td>

<td class="text-end">

{{ number_format($totalSortieUSD,2,',',' ') }}

</td>

</tr>

</tfoot>

</table>

</div>
{{-- SOLDE --}}

<div class="row mt-4">

    <div class="col-md-6">

        <div class="card border-success shadow-sm">

            <div class="card-body text-center">

                <h6 class="text-muted mb-2">
                    Solde CDF
                </h6>

                <h3 class="text-success fw-bold">

                    {{ number_format(
                        $totalEntreeCDF - $totalSortieCDF,
                        2,
                        ',',
                        ' '
                    ) }}

                    CDF

                </h3>

            </div>

        </div>

    </div>


    <div class="col-md-6">

        <div class="card border-primary shadow-sm">

            <div class="card-body text-center">

                <h6 class="text-muted mb-2">
                    Solde USD
                </h6>

                <h3 class="text-primary fw-bold">

                    {{ number_format(
                        $totalEntreeUSD - $totalSortieUSD,
                        2,
                        ',',
                        ' '
                    ) }}

                    USD

                </h3>

            </div>

        </div>

    </div>

</div>

</div>

</div>

</div>

@endsection