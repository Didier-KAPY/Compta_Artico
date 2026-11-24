@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <section class="p-4 p-lg-5 mb-4 rounded-4 text-white" style="background:linear-gradient(125deg,#123b68,#087e8b)">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="small text-uppercase opacity-75">Espace caisse</span>
                <h2 class="mb-1">Bonjour, {{ $user->prenom }} {{ $user->nom }}</h2>
                <p class="mb-0 opacity-75">Suivi des entrées, des sorties et des bons à traiter.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('entree-caisses.create') }}" class="btn btn-light"><i class="bi bi-plus-circle me-1"></i>Nouvelle entrée</a>
                <a href="{{ route('sortie-caisses.create') }}" class="btn btn-outline-light"><i class="bi bi-dash-circle me-1"></i>Nouvelle sortie</a>
            </div>
        </div>
    </section>

    @include('partials.dashboard-month', ['periodDescription' => 'Période des opérations de caisse'])

    <div class="row g-3 mb-4">
        @foreach([
            ['Entrées du mois', $cashier['entry_count'], 'box-arrow-in-down', 'success', $cashier['entry_totals']],
            ['Sorties du mois', $cashier['output_count'], 'box-arrow-up', 'danger', $cashier['output_totals']],
        ] as [$label, $count, $icon, $color, $totals])
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100"><div class="card-body d-flex gap-3 align-items-start">
                    <span class="rounded-3 bg-{{ $color }}-subtle text-{{ $color }} p-3"><i class="bi bi-{{ $icon }} fs-4"></i></span>
                    <div class="flex-grow-1"><span class="text-muted">{{ $label }} · {{ $monthLabel }}</span><div class="fs-3 fw-bold">{{ number_format($count, 0, ',', ' ') }}</div>
                        <div class="small text-muted">Montants enregistrés :
                            @forelse(['CDF', 'USD'] as $currency)
                                <span class="d-inline-block me-2">{{ number_format((float) ($totals[$currency] ?? 0), 2, ',', ' ') }} {{ $currency }}</span>
                            @empty — @endforelse
                        </div>
                    </div>
                </div></div>
            </div>
        @endforeach
        <div class="col-md-6"><a href="{{ route('entree-caisses.index', ['statut' => 'En attente']) }}" class="card border-0 shadow-sm h-100 text-decoration-none text-dark"><div class="card-body d-flex align-items-center gap-3"><span class="rounded-3 bg-warning-subtle text-warning p-3"><i class="bi bi-hourglass-split fs-4"></i></span><div><span class="text-muted">Entrées en attente</span><div class="fs-3 fw-bold">{{ number_format($cashier['pending_entries'], 0, ',', ' ') }}</div></div></div></a></div>
        <div class="col-md-6"><a href="{{ route('sortie-caisses.index', ['statut' => 'En attente']) }}" class="card border-0 shadow-sm h-100 text-decoration-none text-dark"><div class="card-body d-flex align-items-center gap-3"><span class="rounded-3 bg-warning-subtle text-warning p-3"><i class="bi bi-hourglass-split fs-4"></i></span><div><span class="text-muted">Sorties en attente</span><div class="fs-3 fw-bold">{{ number_format($cashier['pending_outputs'], 0, ',', ' ') }}</div></div></div></a></div>
    </div>

    <section class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"><h5 class="mb-0">Dernières opérations de caisse</h5><span class="text-muted small">10 opérations récentes</span></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Référence</th><th>Motif / bénéficiaire</th><th class="text-end">Montant</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                @forelse($cashier['recent'] as $operation)
                    <tr><td>{{ $operation['date']?->format('d/m/Y') ?? '—' }}</td><td><span class="badge text-bg-{{ $operation['kind'] === 'Entrée' ? 'success' : 'danger' }}">{{ $operation['kind'] }}</span></td><td>{{ $operation['numero'] ?: '—' }}</td><td>{{ $operation['description'] }}</td><td class="text-end fw-semibold">{{ number_format($operation['amount'], 2, ',', ' ') }} {{ $operation['currency'] }}</td><td><span class="badge text-bg-{{ $operation['status'] === "Valid\u{00C3}\u{00A9}" ? 'success' : ($operation['status'] === 'En attente' ? 'warning' : 'secondary') }}">{{ $operation['status'] ?: '—' }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ $operation['url'] }}">Ouvrir</a></td></tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">Aucune opération de caisse enregistrée.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
</div>
@endsection
