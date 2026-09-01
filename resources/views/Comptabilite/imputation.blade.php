@extends('layouts.app')
@section('title', 'Journal des opérations diverses')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-2 mb-3"><div><h4 class="mb-0"><i class="bi bi-table me-2"></i>Journal des opérations diverses</h4><small class="text-muted">Écritures issues uniquement des BRC validés</small></div>@include('partials.period-export-buttons', ['rapport' => 'operations-diverses', 'exportParams' => ['date_debut' => request('date_debut', today()->toDateString()), 'date_fin' => request('date_fin', today()->toDateString()), 'reference' => request('reference')]])</div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('comptabilite.imputation-compte') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="reference" class="form-label fw-bold">Numéro de référence</label>
                    <input type="search" id="reference" name="reference" class="form-control" value="{{ request('reference') }}" placeholder="Ex. BRC-20260820-000015">
                </div>
                <div class="col-md-3">
                    <label for="date_debut" class="form-label fw-bold">Date début</label>
                    <input type="date" id="date_debut" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label fw-bold">Date fin</label>
                    <input type="date" id="date_fin" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i>Filtrer</button>
                    <a href="{{ route('comptabilite.imputation-compte') }}" class="btn btn-outline-secondary" title="Réinitialiser"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
            @if(!request()->filled('date_debut') && !request()->filled('date_fin'))
                <small class="text-muted d-block mt-2"><i class="bi bi-calendar-day me-1"></i>Affichage des opérations du jour.</small>
            @endif
        </div>
    </div>
    @php
        $montantOriginal = function ($journal, $ecriture, $brc) {
            if (! $brc) return max((float) $ecriture->debit_cdf, (float) $ecriture->credit_cdf);
            if ((int) $ecriture->liste_des_comptes_id === (int) $brc->journalType?->liste_des_comptes_id) return (float) $brc->total;
            $ligne = $brc->lignes->first(fn($item) => (int) $item->liste_des_comptes_id === (int) $ecriture->liste_des_comptes_id && trim($item->libelle) === trim($ecriture->libelle))
                ?? $brc->lignes->firstWhere('liste_des_comptes_id', $ecriture->liste_des_comptes_id);
            return (float) ($ligne?->montant ?? max((float) $ecriture->debit_cdf, (float) $ecriture->credit_cdf));
        };
    @endphp
    <div class="card shadow-sm border-0"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-dark"><tr><th>Date BRC</th><th>Validé par</th><th>Référence BRC</th><th>Compte débit</th><th>Compte crédit</th><th>Libellé</th><th class="text-end">Montant débit</th><th class="text-end">Montant crédit</th><th>Monnaie</th><th>Statut</th><th class="text-center">Action</th></tr></thead>
            <tbody>
            @forelse($journaux as $journal)
                @foreach($journal->ecritures as $ecriture)
                @php
                    $brc = $journal->brcs->first();
                    $compte = trim(($ecriture->compte?->compte ?? '—').' — '.($ecriture->compte?->designation ?? ''));
                    $montant = $montantOriginal($journal, $ecriture, $brc);
                @endphp
                <tr>
                    <td class="text-nowrap">{{ $brc?->date?->format('d/m/Y') ?? $ecriture->date?->format('d/m/Y') ?? $journal->date?->format('d/m/Y') }}</td>
                    <td>{{ trim(($brc?->validateur?->prenom ?? $journal->validateur?->prenom ?? '').' '.($brc?->validateur?->nom ?? $journal->validateur?->nom ?? '')) ?: '—' }}</td>
                    <td class="fw-semibold">{{ $brc?->reference ?? $journal->reference }}</td>
                    <td>{{ (float) $ecriture->debit_cdf > 0 ? $compte : '—' }}</td>
                    <td>{{ (float) $ecriture->credit_cdf > 0 ? $compte : '—' }}</td>
                    <td>{{ $ecriture->libelle ?: $journal->description ?: '—' }}</td>
                    <td class="text-end">{{ (float) $ecriture->debit_cdf > 0 ? number_format($montant, 2, ',', ' ') : '—' }}</td>
                    <td class="text-end">{{ (float) $ecriture->credit_cdf > 0 ? number_format($montant, 2, ',', ' ') : '—' }}</td>
                    <td><span class="badge bg-secondary">{{ $brc?->monnaie ?? $journal->monnaie }}</span></td>
                    <td><span class="badge {{ mb_strtolower($ecriture->statut) === 'validé' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $ecriture->statut }}</span></td>
                    <td class="text-center"><a href="{{ route('journaux.show', $journal) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Voir</a></td>
                </tr>
                @endforeach
            @empty
                <tr><td colspan="11" class="text-center text-muted py-5"><i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>Aucune opération diverse trouvée pour cette période.</td></tr>
            @endforelse
            </tbody>
            @if($journaux->isNotEmpty())
@php($totalOriginal = $journaux->sum(fn($j) => (float) ($j->brcs->first()?->total ?? $j->ecritures->sum('debit_cdf'))))
<tfoot class="table-light fw-bold"><tr><td colspan="6" class="text-end">Totaux de la page</td><td class="text-end">{{ number_format($totalOriginal, 2, ',', ' ') }}</td><td class="text-end">{{ number_format($totalOriginal, 2, ',', ' ') }}</td><td colspan="3"></td></tr></tfoot>
@endif
        </table>
    </div></div>@if($journaux->hasPages())<div class="card-footer bg-white">{{ $journaux->links() }}</div>@endif</div>
</div>
@endsection
