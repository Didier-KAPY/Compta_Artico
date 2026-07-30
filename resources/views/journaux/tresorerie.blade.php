@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 sage-screen">
    <div class="sage-titlebar d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div><span class="sage-module">Trésorerie</span><h3><i class="bi bi-bank2 me-2"></i>Situation de trésorerie</h3><p>Soldes des comptes de caisse, banque et Mobile Money</p></div>
        <div class="d-flex gap-2 no-print"><a href="{{ route('journaux.releve', request()->only('date_debut','date_fin')) }}" class="btn btn-outline-primary"><i class="bi bi-list-columns-reverse me-1"></i> Relevé détaillé</a><button class="btn btn-light border" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimer</button></div>
    </div>

    <div class="d-flex justify-content-end mb-2 no-print">@include('partials.period-export-buttons', ['rapport'=>'tresorerie','exportParams'=>['date_debut'=>$dateDebut,'date_fin'=>$dateFin]])</div>
    <form method="GET" action="{{ route('journaux.tresorerie') }}" class="sage-filter no-print">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3"><label class="form-label">Du</label><input type="date" name="date_debut" value="{{ $dateDebut }}" class="form-control"></div>
            <div class="col-md-4 col-lg-3"><label class="form-label">Au</label><input type="date" name="date_fin" value="{{ $dateFin }}" class="form-control"></div>
            <div class="col-md-4 col-lg-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="bi bi-arrow-repeat me-1"></i> Actualiser</button><a href="{{ route('journaux.tresorerie') }}" class="btn btn-light border" title="Réinitialiser"><i class="bi bi-x-lg"></i></a></div>
            <div class="col-lg-3 text-lg-end"><span class="period-label">Période : {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</span></div>
        </div>
    </form>

    <div class="row g-3 my-1">
        @foreach([
            ['Solde en caisse CDF', 'cash-stack', 'caisse_cdf', 'CDF'],
            ['Solde en caisse USD', 'cash-stack', 'caisse_usd', 'USD'],
            ['Solde en banque CDF', 'bank', 'banque_cdf', 'CDF'],
            ['Solde en banque USD', 'bank', 'banque_usd', 'USD'],
            ['Solde Mobile Money CDF', 'phone', 'mobile_cdf', 'CDF'],
            ['Solde Mobile Money USD', 'phone', 'mobile_usd', 'USD'],
        ] as [$label, $icon, $key, $currency])
        @php $balance = (float) ($totaux[$key] ?? 0); @endphp
        <div class="col-md-6 col-xl-4">
            <div class="sage-balance-card">
                <div class="balance-head"><span>{{ $label }}</span><i class="bi bi-{{ $icon }}"></i></div>
                <div class="balance-value {{ $balance < 0 ? 'text-danger' : '' }}">{{ number_format($balance, 2, ',', ' ') }} <small>{{ $currency }}</small></div>
                <small class="text-muted">Disponible au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</small>
            </div>
        </div>
        @endforeach
    </div>

    <div class="sage-panel mt-3">
        <div class="sage-panel-head"><div><strong>Position par compte de trésorerie</strong><small>Mouvements validés uniquement</small></div><span>{{ $tresorerie->count() }} compte(s)</span></div>
        <div class="table-responsive"><table class="table sage-grid mb-0"><thead><tr><th>Code journal</th><th>Compte</th><th>Désignation</th><th>Nature</th><th class="text-end">Entrées CDF</th><th class="text-end">Sorties CDF</th><th class="text-end">Solde CDF</th><th class="text-end">Entrées USD</th><th class="text-end">Sorties USD</th><th class="text-end">Solde USD</th></tr></thead><tbody>
            @forelse($tresorerie as $ligne)
                @php $cdf = (float)$ligne->entree_cdf - (float)$ligne->sortie_cdf; $usd = (float)$ligne->entree_usd - (float)$ligne->sortie_usd; @endphp
                <tr><td><span class="journal-code">{{ $ligne->journalType?->code ?? '—' }}</span></td><td class="fw-semibold">{{ $ligne->journalType?->compte?->compte ?? '—' }}</td><td>{{ $ligne->journalType?->compte?->designation ?? '—' }}</td><td><span class="nature-badge">{{ ucfirst(str_replace('_',' ', $ligne->journalType?->nature ?? '')) }}</span></td><td class="text-end amount-in">{{ number_format($ligne->entree_cdf,2,',',' ') }}</td><td class="text-end amount-out">{{ number_format($ligne->sortie_cdf,2,',',' ') }}</td><td class="text-end fw-bold {{ $cdf < 0 ? 'text-danger' : '' }}">{{ number_format($cdf,2,',',' ') }}</td><td class="text-end amount-in">{{ number_format($ligne->entree_usd,2,',',' ') }}</td><td class="text-end amount-out">{{ number_format($ligne->sortie_usd,2,',',' ') }}</td><td class="text-end fw-bold {{ $usd < 0 ? 'text-danger' : '' }}">{{ number_format($usd,2,',',' ') }}</td></tr>
            @empty<tr><td colspan="10" class="empty-state"><i class="bi bi-inbox"></i>Aucun mouvement validé sur cette période.</td></tr>@endforelse
        </tbody><tfoot><tr><td colspan="4">TOTAL GÉNÉRAL</td><td class="text-end">{{ number_format($totaux['cdf_entree'],2,',',' ') }}</td><td class="text-end">{{ number_format($totaux['cdf_sortie'],2,',',' ') }}</td><td class="text-end">{{ number_format($totaux['cdf_solde'],2,',',' ') }}</td><td class="text-end">{{ number_format($totaux['usd_entree'],2,',',' ') }}</td><td class="text-end">{{ number_format($totaux['usd_sortie'],2,',',' ') }}</td><td class="text-end">{{ number_format($totaux['usd_solde'],2,',',' ') }}</td></tr></tfoot></table></div>
    </div>
</div>
@include('journaux._sage_styles')
@endsection
