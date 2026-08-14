@extends('layouts.app')

@section('content')
<div class="dashboard-shell">
    <section class="welcome-panel mb-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                @if($user->photo)
                    <img src="{{ asset('storage/'.$user->photo) }}" class="welcome-avatar" alt="Photo de {{ $user->prenom }}">
                @else
                    <div class="welcome-avatar avatar-fallback"><i class="bi bi-person"></i></div>
                @endif
                <div>
                    <span class="eyebrow">Tableau de bord</span>
                    <h2 class="welcome-user-name mb-1">Bonjour, {{ $user->prenom }} {{ $user->nom }}</h2>
                    <span class="role-pill"><i class="bi bi-shield-check"></i> {{ $user->role?->designation ?? 'Sans rôle' }}</span>
                </div>
            </div>
            <div class="welcome-meta text-md-end">
                <div id="dashboardClock" class="current-time"></div>
                <div>{{ now()->translatedFormat('l d F Y') }}</div>
                <small>Dernière connexion : {{ $user->last_logged_in ? \Carbon\Carbon::parse($user->last_logged_in)->translatedFormat('d/m/Y à H:i') : 'Première connexion' }}</small>
            </div>
        </div>
    </section>

    @if($sections['exchange'])
        <div class="rate-card mb-4">
            <span class="eyebrow rate-title">Taux du jour</span>
            <i class="bi bi-currency-exchange"></i>
            <h3>1 USD = {{ number_format((float) ($exchange_rate?->taux_de_change ?? 0), 2, ',', ' ') }} CDF</h3>
            <div class="rate-help">Taux utilisé pour convertir les opérations en USD vers le CDF.</div>
            <small>Mis à jour {{ $exchange_rate?->updated_at?->translatedFormat('d/m/Y à H:i') ?? '—' }}</small>
        </div>
    @endif

    @if($sections['statistics'])
        @php
            $operationalCards = [
                ['brc', 'BRC', 'file-earmark-text', 'info'], ['cash_in', "Bons d'entrée", 'box-arrow-in-down', 'emerald'],
                ['cash_out', 'Bons de sortie', 'box-arrow-up', 'danger'], ['needs', 'États de besoin', 'clipboard-check', 'warning'],
            ];
            $configurationCards = [
                ['users', 'Utilisateurs', 'people', 'primary'],
                ['accounts', 'Comptes comptables', 'list-ol', 'violet'],
            ];
            $cards = $user->isAccounting()
                ? $operationalCards
                : array_merge($configurationCards, $operationalCards);
        @endphp
        <div class="section-heading"><div><span class="eyebrow">{{ $user->isAccounting() ? 'Activité financière' : 'Vue globale' }}</span><h4>Statistiques</h4><small class="section-note">{{ $user->isAccounting() ? 'Nombre d’opérations actuellement enregistrées.' : 'Nombre total d’éléments enregistrés dans chaque module.' }}</small></div></div>
        <div class="row g-3 mb-4">
            @foreach($cards as [$key, $label, $icon, $color])
                <div class="col-6 col-lg-4 col-xl-3">
                    <div class="metric-card h-100">
                        <span class="metric-icon metric-{{ $color }}"><i class="bi bi-{{ $icon }}"></i></span>
                        <div><span class="metric-label">{{ $label }}</span><strong>{{ number_format($statistics[$key], 0, ',', ' ') }}</strong></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($sections['treasury_situation'])
        @php
            $treasuryCards = [
                ['Caisse', 'caisse', 'cash-stack', 'success'],
                ['Banque', 'banque', 'bank', 'primary'],
                ['Mobile Money', 'mobile', 'phone', 'warning'],
            ];
        @endphp
        <div class="section-heading">
            <div><span class="eyebrow">Liquidités disponibles</span><h4>Situation de trésorerie</h4><small class="section-note">Mouvements validés jusqu’à aujourd’hui. Solde = entrées − sorties.</small></div>
            <a href="{{ route('journaux.tresorerie') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i> Voir le détail</a>
        </div>
        <div class="row g-3 mb-3">
            @foreach($treasuryCards as [$label, $key, $icon, $color])
                <div class="col-md-4">
                    <div class="cash-card border-{{ $color }} h-100">
                        <i class="bi bi-{{ $icon }} text-{{ $color }}"></i>
                        <div>
                            <span>{{ $label }}</span>
                            <strong class="{{ $treasury_situation['totals'][$key.'_cdf'] < 0 ? 'text-danger' : '' }}">{{ number_format($treasury_situation['totals'][$key.'_cdf'], 2, ',', ' ') }} <small>CDF</small></strong>
                            <strong class="{{ $treasury_situation['totals'][$key.'_usd'] < 0 ? 'text-danger' : '' }}">{{ number_format($treasury_situation['totals'][$key.'_usd'], 2, ',', ' ') }} <small>USD</small></strong>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="panel-card mb-4">
            <div class="panel-title">
                <div><span class="eyebrow">Solde à ce jour</span><h5>Disponibilités par compte</h5></div>
                <div class="text-end">
                    <strong class="d-block">{{ number_format($treasury_situation['totals']['total_cdf'], 2, ',', ' ') }} CDF</strong>
                    <strong class="d-block">{{ number_format($treasury_situation['totals']['total_usd'], 2, ',', ' ') }} USD</strong>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table dashboard-table align-middle mb-0">
                    <thead><tr><th>Journal</th><th>Compte</th><th>Désignation</th><th>Nature</th><th class="text-end">Solde CDF</th><th class="text-end">Solde USD</th></tr></thead>
                    <tbody>
                        @forelse($treasury_situation['accounts'] as $account)
                            <tr>
                                <td><span class="badge bg-light text-dark">{{ $account['code'] }}</span></td>
                                <td>{{ $account['account'] }}</td>
                                <td>{{ $account['designation'] }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $account['nature'])) }}</td>
                                <td class="text-end fw-semibold {{ $account['balance_cdf'] < 0 ? 'text-danger' : '' }}">{{ number_format($account['balance_cdf'], 2, ',', ' ') }}</td>
                                <td class="text-end fw-semibold {{ $account['balance_usd'] < 0 ? 'text-danger' : '' }}">{{ number_format($account['balance_usd'], 2, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i> Aucun mouvement de trésorerie validé.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($sections['cash'])
        <div class="section-heading"><div><span class="eyebrow">Liquidités</span><h4>Situation de caisse</h4><small class="section-note">Résumé des entrées, sorties et soldes enregistrés, séparés en CDF et USD.</small></div></div>
        <div class="row g-3 mb-4">
            @foreach([
                ['Entrées CDF', $cash['in_cdf'], 'arrow-down-left', 'success', 'CDF'], ['Sorties CDF', $cash['out_cdf'], 'arrow-up-right', 'danger', 'CDF'],
                ['Solde CDF', $cash['balance_cdf'], 'wallet2', 'primary', 'CDF'], ['Entrées USD', $cash['in_usd'], 'arrow-down-left', 'success', 'USD'],
                ['Sorties USD', $cash['out_usd'], 'arrow-up-right', 'danger', 'USD'], ['Solde USD', $cash['balance_usd'], 'wallet2', 'primary', 'USD']
            ] as [$label, $value, $icon, $color, $currency])
                <div class="col-sm-6 col-xl-4"><div class="cash-card border-{{ $color }}"><i class="bi bi-{{ $icon }} text-{{ $color }}"></i><div><span>{{ $label }}</span><strong>{{ number_format($value, 2, ',', ' ') }} <small>{{ $currency }}</small></strong></div></div></div>
            @endforeach
        </div>
    @endif

    @if($sections['validations'])
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="panel-card h-100">
                    <div class="panel-title"><div><span class="eyebrow">À traiter</span><h5>Validations en attente</h5><small class="section-note">Nombre de dossiers qui attendent encore une décision.</small></div><i class="bi bi-bell"></i></div>
                    <div class="row g-3">
                        @foreach([['brc', 'BRC', 'file-earmark-text'], ['cash_in', "Bons d'entrée", 'box-arrow-in-down'], ['needs', 'États de besoin', 'clipboard'], ['cash_out', 'Bons de sortie', 'box-arrow-up'], ['entries', 'Écritures', 'pencil-square'], ['journals', 'Journaux', 'journal-text']] as [$key, $label, $icon])
                            <div class="col-6"><div class="validation-item"><i class="bi bi-{{ $icon }}"></i><span>{{ $label }}</span><span class="badge rounded-pill bg-danger">{{ $validations[$key] }}</span></div></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <div class="d-flex align-items-start gap-3"><i class="bi bi-exclamation-triangle fs-4"></i><div class="w-100">
                <strong>Alertes comptables</strong>
                <div class="row g-2 mt-1 small">
                    <div class="col-md-4">{{ $accounting_alerts['journaux_sans_piece'] }} journal(aux) sans pièce justificative</div>
                    <div class="col-md-4">{{ $accounting_alerts['journaux_non_regroupes'] }} journal(aux) non regroupé(s)</div>
                    <div class="col-md-4">{{ $accounting_alerts['jours_ouverts'] }} journée(s) encore ouverte(s)</div>
                </div>
            </div></div>
        </div>
    @endif

    @if($sections['charts'])
        <div class="section-heading"><div><span class="eyebrow">Analyse</span><h4>Indicateurs graphiques</h4><small class="section-note">Évolution des mouvements, répartition des opérations et modes de paiement.</small></div></div>
        <div class="row g-3 mb-4">
            <div class="col-xl-7"><div class="panel-card chart-card"><h5>Entrées vs sorties par mois</h5><canvas id="cashFlowChart"></canvas></div></div>
            <div class="col-xl-5"><div class="panel-card chart-card"><h5>Activité par opération</h5><canvas id="operationsChart"></canvas></div></div>
            <div class="col-xl-7"><div class="panel-card chart-card"><h5>Évolution mensuelle de la trésorerie (CDF)</h5><canvas id="treasuryChart"></canvas></div></div>
            <div class="col-xl-5"><div class="panel-card chart-card"><h5>Modes de paiement</h5><canvas id="paymentChart"></canvas></div></div>
        </div>
    @endif

    @if($sections['shortcuts'])
        <div class="panel-card mb-4">
            <div class="panel-title"><div><span class="eyebrow">Accès direct</span><h5>Raccourcis</h5></div></div>
            <div class="shortcut-grid">
                @php
                    $roleName = mb_strtolower($user->role?->designation ?? '');
                    $isAdmin = $user->isSuperAdmin() || $user->isManagement();
                    $isCash = in_array($roleName, ['caissier', 'caissière', 'trésorier', 'trésorière']);
                    $isAccounting = $user->isAccounting();
                @endphp
                @can('createEtatBesoin')<a href="{{ route('etat-besoins.create') }}"><i class="bi bi-clipboard-plus"></i><span>Nouvel état de besoin</span></a>@endcan
                @can('manageEntreeCaisse')<a href="{{ route('entree-caisses.create') }}"><i class="bi bi-box-arrow-in-down"></i><span>Nouvelle entrée</span></a>@endcan
                @if($isAdmin || $isCash)<a href="{{ route('sortie-caisses.create') }}"><i class="bi bi-box-arrow-up"></i><span>Nouvelle sortie</span></a>@endif
                @can('manageJournaux')<a href="{{ route('journaux.create') }}"><i class="bi bi-journal-plus"></i><span>Nouveau journal</span></a>@endcan
                @if(in_array($roleName, ['super admin', 'admin', 'comptable'], true))<a href="{{ route('brc.create') }}"><i class="bi bi-file-earmark-plus"></i><span>Nouveau BRC</span></a>@endif
                @if(config('features.accounting') && ($isAdmin || $isAccounting))<a href="{{ route('ecritures.create') }}"><i class="bi bi-pencil-square"></i><span>Nouvelle écriture</span></a>@endif
                @if(config('features.accounting') && ($isAdmin || $isAccounting))<a href="{{ route('balance.index') }}"><i class="bi bi-bar-chart"></i><span>Balance</span></a><a href="{{ route('grandlivre.index') }}"><i class="bi bi-book"></i><span>Grand livre</span></a><a href="{{ route('comptabilite.etats-financiers.index') }}"><i class="bi bi-file-earmark-bar-graph"></i><span>États financiers</span></a>@endif
            </div>
        </div>
    @endif

    @if($sections['operations'])
        <div class="panel-card">
            <div class="panel-title"><div><span class="eyebrow">Activité récente</span><h5>10 dernières opérations</h5><small class="section-note">Journaux les plus récents, classés par date d’opération.</small></div></div>
            <div class="table-responsive"><table class="table dashboard-table align-middle mb-0"><thead><tr><th>Date</th><th>Référence</th><th>Type</th><th>Montant</th><th>Monnaie</th>@if($user->isSuperAdmin())<th>Validé par</th>@endif<th>Statut</th></tr></thead><tbody>
                @forelse($latest_operations as $operation)
                    <tr><td>{{ $operation->date?->format('d/m/Y') }}</td><td class="fw-semibold">{{ $operation->reference }}</td><td>{{ ucfirst($operation->type) }}</td><td>{{ number_format($operation->montant_ttc, 2, ',', ' ') }}</td><td>{{ $operation->monnaie }}</td>@if($user->isSuperAdmin())<td>{{ trim(($operation->validateur?->prenom ?? '').' '.($operation->validateur?->nom ?? '')) ?: 'Non validé' }}</td>@endif<td><span class="status status-{{ $operation->statut === 'Validé' ? 'success' : ($operation->statut === 'Rejeté' ? 'danger' : 'warning') }}">{{ $operation->statut }}</span></td></tr>
                @empty<tr><td colspan="{{ $user->isSuperAdmin() ? 7 : 6 }}" class="text-center text-muted py-4">Aucune opération enregistrée</td></tr>@endforelse
            </tbody></table></div>
        </div>
    @endif
</div>

<style>
.dashboard-shell{--ink:#14213d;--muted:#64748b;--line:#e7ebf1;color:var(--ink)}.welcome-panel{padding:1.5rem 1.7rem;border-radius:20px;background:linear-gradient(135deg,#0f2747,#1d4f91);color:#fff;box-shadow:0 14px 35px rgba(15,39,71,.18)}.welcome-avatar{width:70px;height:70px;border-radius:18px;object-fit:cover;border:3px solid rgba(255,255,255,.35)}.avatar-fallback{display:grid;place-items:center;background:rgba(255,255,255,.16);font-size:2rem}.eyebrow{display:block;text-transform:uppercase;letter-spacing:.11em;font-size:.68rem;font-weight:700;color:#718096;margin-bottom:.25rem}.welcome-panel .eyebrow{color:#9cc6ff}.role-pill{display:inline-flex;gap:.35rem;align-items:center;padding:.25rem .65rem;border-radius:99px;background:rgba(255,255,255,.13);font-size:.8rem}.welcome-meta{color:#dbeafe}.current-time{font-size:1.45rem;font-weight:700}.section-heading{display:flex;justify-content:space-between;align-items:end;margin:1.2rem 0 .8rem}.section-heading h4,.panel-title h5{margin:0;font-weight:700}.metric-card,.cash-card,.panel-card{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 5px 18px rgba(15,23,42,.045)}.metric-card{display:flex;align-items:center;gap:1rem;padding:1.15rem;transition:.2s}.metric-card:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(15,23,42,.09)}.metric-icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;font-size:1.25rem}.metric-primary{background:#e8f0ff;color:#2563eb}.metric-info{background:#e7f8ff;color:#0891b2}.metric-success,.metric-emerald{background:#e9fbf3;color:#059669}.metric-danger{background:#ffeded;color:#dc2626}.metric-warning{background:#fff6dd;color:#d97706}.metric-violet{background:#f3edff;color:#7c3aed}.metric-teal{background:#e6fbf8;color:#0f766e}.metric-slate{background:#eef2f6;color:#475569}.metric-label{display:block;color:var(--muted);font-size:.8rem}.metric-card strong{font-size:1.55rem}.cash-card{padding:1.1rem 1.2rem;display:flex;gap:1rem;align-items:center;border-left-width:4px!important}.cash-card>i{font-size:1.55rem}.cash-card span{display:block;color:var(--muted);font-size:.8rem}.cash-card strong{font-size:1.15rem}.cash-card small{font-size:.7rem}.panel-card{padding:1.25rem}.panel-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem}.panel-title>i{font-size:1.3rem;color:#64748b}.validation-item{padding:.8rem;border-radius:12px;background:#f8fafc;display:flex;align-items:center;gap:.65rem}.validation-item .badge{margin-left:auto}.rate-card{position:relative;overflow:hidden;border-radius:16px;padding:1.4rem;background:linear-gradient(135deg,#075985,#0891b2);color:#fff;box-shadow:0 10px 25px rgba(8,145,178,.2)}.rate-card>i{position:absolute;right:1rem;top:.6rem;font-size:4rem;opacity:.13}.rate-card h3{margin:.8rem 0 .25rem;font-size:1.35rem}.chart-card{height:360px}.chart-card canvas{max-height:290px}.shortcut-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem}.shortcut-grid a{padding:1rem;border:1px solid var(--line);border-radius:13px;text-decoration:none;color:var(--ink);display:flex;align-items:center;gap:.7rem;background:#fbfcfe;transition:.2s}.shortcut-grid a:hover{color:#0d6efd;border-color:#9ec5fe;transform:translateY(-2px)}.shortcut-grid i{font-size:1.25rem}.dashboard-table thead th{background:#f8fafc;color:#64748b;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--line)}.dashboard-table td{font-size:.86rem;border-color:#eef2f6}.status{display:inline-block;padding:.25rem .6rem;border-radius:99px;font-size:.72rem;font-weight:700}.status-success{background:#dcfce7;color:#15803d}.status-danger{background:#fee2e2;color:#b91c1c}.status-warning{background:#fff4cf;color:#a16207}@media(max-width:991px){.shortcut-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:575px){.welcome-panel{padding:1.2rem}.welcome-avatar{width:58px;height:58px}.welcome-panel h2{font-size:1.25rem}.shortcut-grid{grid-template-columns:1fr}.chart-card{height:320px}.metric-card{padding:.9rem}.metric-icon{width:42px;height:42px}.metric-card strong{font-size:1.25rem}}
.section-note{display:block;margin-top:.3rem;color:var(--muted);font-size:.78rem;font-weight:400}.rate-help{margin:-.1rem 0 .45rem;color:rgba(255,255,255,.82);font-size:.82rem}
.welcome-panel .welcome-user-name{color:#fff!important}
.rate-card .rate-title,.rate-card h3{color:#fff!important;opacity:1}
</style>
@endsection

@if($sections['charts'])
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const data = @json($charts);
    const common = {responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{usePointStyle:true,boxWidth:8}}},scales:{y:{beginAtZero:true,grid:{color:'#eef2f6'}},x:{grid:{display:false}}}};
    new Chart(document.getElementById('cashFlowChart'),{type:'bar',data:{labels:data.labels,datasets:[{label:'Entrées CDF',data:data.in_cdf,backgroundColor:'#10b981'},{label:'Sorties CDF',data:data.out_cdf,backgroundColor:'#ef4444'},{label:'Entrées USD',data:data.in_usd,backgroundColor:'#38bdf8'},{label:'Sorties USD',data:data.out_usd,backgroundColor:'#f59e0b'}]},options:common});
    new Chart(document.getElementById('operationsChart'),{type:'doughnut',data:{labels:['Recettes','Achats','Dépenses','Ventes'],datasets:[{data:data.operations,backgroundColor:['#2563eb','#f59e0b','#ef4444','#10b981'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%'}});
    new Chart(document.getElementById('treasuryChart'),{type:'line',data:{labels:data.labels,datasets:[{label:'Solde cumulé CDF',data:data.treasury,borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.12)',fill:true,tension:.35,pointRadius:3}]},options:common});
    new Chart(document.getElementById('paymentChart'),{type:'polarArea',data:{labels:['Espèces','Banque','Mobile Money'],datasets:[{data:data.payments,backgroundColor:['rgba(16,185,129,.75)','rgba(37,99,235,.75)','rgba(124,58,237,.75)'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false}});
});
</script>
@endpush
@endif

@push('scripts')
<script>
const dashboardClock = document.getElementById('dashboardClock');
const updateClock = () => { if (dashboardClock) dashboardClock.textContent = new Intl.DateTimeFormat('fr-FR',{hour:'2-digit',minute:'2-digit',second:'2-digit'}).format(new Date()); };
updateClock(); setInterval(updateClock, 1000);

// Actualise les données de tous les tableaux de bord toutes les 30 secondes.
window.setInterval(() => window.location.reload(), 30000);
</script>
@endpush
