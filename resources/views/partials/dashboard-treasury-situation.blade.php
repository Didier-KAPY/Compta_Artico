@php
    $treasuryCards = [
        ['Caisse', 'caisse', 'cash-stack', 'success'],
        ['Banque', 'banque', 'bank', 'primary'],
        ['Mobile Money', 'mobile', 'phone', 'warning'],
    ];
@endphp
<div class="section-heading">
    <div><span class="eyebrow">Liquidités disponibles</span><h4>Situation de trésorerie</h4><small class="section-note">Mouvements validés jusqu’à aujourd’hui. Solde = entrées − sorties.</small></div>
    <a href="{{ route('journaux.tresorerie') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Voir le détail</a>
</div>
<div class="row g-3 mb-3">
    @foreach($treasuryCards as [$label, $key, $icon, $color])
        <div class="col-md-4"><div class="cash-card border-{{ $color }} h-100"><i class="bi bi-{{ $icon }} text-{{ $color }}"></i><div><span>{{ $label }}</span><strong class="{{ $treasury_situation['totals'][$key.'_cdf'] < 0 ? 'text-danger' : '' }}">{{ number_format($treasury_situation['totals'][$key.'_cdf'], 2, ',', ' ') }} <small>CDF</small></strong><strong class="{{ $treasury_situation['totals'][$key.'_usd'] < 0 ? 'text-danger' : '' }}">{{ number_format($treasury_situation['totals'][$key.'_usd'], 2, ',', ' ') }} <small>USD</small></strong></div></div></div>
    @endforeach
</div>
<div class="panel-card mb-4">
    <div class="panel-title"><div><span class="eyebrow">Solde à ce jour</span><h5>Disponibilités par compte</h5></div><div class="text-end"><strong class="d-block">{{ number_format($treasury_situation['totals']['total_cdf'], 2, ',', ' ') }} CDF</strong><strong class="d-block">{{ number_format($treasury_situation['totals']['total_usd'], 2, ',', ' ') }} USD</strong></div></div>
    <div class="table-responsive"><table class="table dashboard-table align-middle mb-0"><thead><tr><th>Journal</th><th>Compte</th><th>Désignation</th><th>Nature</th><th class="text-end">Solde CDF</th><th class="text-end">Solde USD</th></tr></thead><tbody>
        @forelse(collect($treasury_situation['accounts'])->filter(fn ($account) => round((float) $account['balance_cdf'], 2) != 0 || round((float) $account['balance_usd'], 2) != 0) as $account)
            <tr><td><span class="badge bg-light text-dark">{{ $account['code'] }}</span></td><td>{{ $account['account'] }}</td><td>{{ $account['designation'] }}</td><td>{{ ucfirst(str_replace('_', ' ', $account['nature'])) }}</td><td class="text-end fw-semibold {{ $account['balance_cdf'] < 0 ? 'text-danger' : '' }}">{{ number_format($account['balance_cdf'], 2, ',', ' ') }}</td><td class="text-end fw-semibold {{ $account['balance_usd'] < 0 ? 'text-danger' : '' }}">{{ number_format($account['balance_usd'], 2, ',', ' ') }}</td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i>Aucun compte avec un solde non nul.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
