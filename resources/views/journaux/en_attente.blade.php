@extends('layouts.app')

@section('title', $journalTitre)

@section('content')
<div class="container-fluid py-4 px-lg-4 journal-waiting" style="--journal-color: {{ $journalCouleur }}; --journal-soft: {{ $journalFond }};">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="page-kicker">Journal automatique</span>
            <h3 class="fw-bold mb-1"><i class="bi {{ $journalIcone }} me-2 journal-color"></i>{{ $journalTitre }}</h3>
            <p class="text-muted mb-0">{{ $journalDescription }}</p>
        </div>
        <a href="{{ route('journaux.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Tous les journaux</a>
    </div>

    @if(session('success'))<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="summary-card"><small>En attente</small><strong>{{ $totaux['nombre'] }}</strong></div></div>
        <div class="col-sm-6 col-xl-3"><div class="summary-card"><small>Total TTC</small><strong>{{ number_format($totaux['ttc'], 2, ',', ' ') }}</strong></div></div>
        <div class="col-sm-6 col-xl-3"><div class="summary-card"><small>Total HT</small><strong>{{ number_format($totaux['ht'], 2, ',', ' ') }}</strong></div></div>
        <div class="col-sm-6 col-xl-3"><div class="summary-card"><small>Total TVA</small><strong>{{ number_format($totaux['tva'], 2, ',', ' ') }}</strong></div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route($routeNom) }}" class="row g-3 align-items-end">
                <div class="col-md-4"><label class="form-label">Référence</label><input type="text" name="reference" value="{{ request('reference') }}" class="form-control" placeholder="Rechercher un numéro de bon"></div>
                <div class="col-md-3"><label class="form-label">Date début</label><input type="date" name="date_debut" value="{{ request('date_debut') }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Date fin</label><input type="date" name="date_fin" value="{{ request('date_fin') }}" class="form-control"></div>
                <div class="col-md-2 d-grid"><button class="btn journal-button"><i class="bi bi-search me-1"></i>Filtrer</button></div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header journal-header text-white py-3"><strong><i class="bi bi-hourglass-split me-2"></i>Liste des journaux</strong></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Référence</th>
                        <th>Validé par</th>
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
                            <td><strong>{{ $journal->reference ?: '—' }}</strong></td>
                            <td>{{ trim(($journal->validateur?->prenom ?? '').' '.($journal->validateur?->nom ?? '')) ?: 'Non validé' }}</td>
                            <td>{{ $journal->date?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                {{ $journal->description ?: '—' }}
                                @if((float) $journal->taux_tva > 0 && (float) $journal->montant_tva > 0)
                                    <small class="d-block text-primary mt-1">
                                        TVA {{ number_format($journal->taux_tva, 2, ',', ' ') }} % ·
                                        HT {{ number_format($journal->montant_ht, 2, ',', ' ') }} ·
                                        TVA {{ number_format($journal->montant_tva, 2, ',', ' ') }} {{ $journal->monnaie }}
                                    </small>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ $journal->monnaie ?? 'CDF' }}</span></td>
                            <td class="text-end fw-bold text-success">{{ number_format($journal->entrees_cdf ?? 0, 2, ',', ' ') }}</td>
                            <td class="text-end fw-bold text-danger">{{ number_format($journal->sorties_cdf ?? 0, 2, ',', ' ') }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($journal->entrees_usd ?? 0, 2, ',', ' ') }}</td>
                            <td class="text-end fw-bold text-danger">{{ number_format($journal->sorties_usd ?? 0, 2, ',', ' ') }}</td>
                            <td>
                                @php $statut = mb_strtolower(trim((string) $journal->statut)); @endphp
                                @if(in_array($statut, ['validé', 'valide'], true))
                                    <span class="badge bg-success">Validé</span>
                                @elseif(in_array($statut, ['rejeté', 'rejete'], true))
                                    <span class="badge bg-danger">Rejeté</span>
                                @else
                                    <span class="badge bg-warning text-dark">En attente</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">Actions</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route($showRouteNom ?? 'journaux.show', $journal) }}"><i class="bi bi-eye me-2"></i>Voir</a></li>
                                        <li><a class="dropdown-item" href="{{ route('journaux.recu', $journal) }}" target="_blank"><i class="bi bi-printer me-2"></i>Imprimer le reçu</a></li>
                                        <li><a class="dropdown-item" href="{{ route('journaux.recu.pdf', $journal) }}"><i class="bi bi-file-earmark-arrow-down me-2"></i>Télécharger le reçu</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-5"><i class="bi bi-journal-x fs-2 d-block mb-2"></i>Aucun journal dans cette catégorie.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $journaux->links() }}</div>
</div>

<style>
.journal-waiting .page-kicker{display:block;color:var(--journal-color);font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;margin-bottom:.25rem}.journal-color{color:var(--journal-color)}.journal-waiting .summary-card{height:100%;padding:1rem 1.15rem;background:#fff;border:1px solid #e7ebf0;border-left:4px solid var(--journal-color);border-radius:12px;box-shadow:0 4px 15px rgba(15,23,42,.06)}.journal-waiting .summary-card small{display:block;color:#6b7280;margin-bottom:.35rem}.journal-waiting .summary-card strong{font-size:1.25rem}.journal-header{background:var(--journal-color)}.journal-button{background:var(--journal-color);border-color:var(--journal-color);color:#fff}.journal-button:hover{background:var(--journal-color);border-color:var(--journal-color);color:#fff;filter:brightness(.9)}
</style>
@endsection