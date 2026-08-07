@extends('layouts.app')
@section('title', 'Clôtures journalières')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><span class="text-primary text-uppercase small fw-bold">Contrôle comptable</span><h2 class="mb-0">Dashboard des clôtures journalières</h2></div>
        <a href="{{ route('parametres.parametre') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Paramètres</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <h5 class="fw-bold"><i class="bi bi-play-circle me-2"></i>Préparer une clôture</h5>
        <form method="GET" action="{{ route('parametres.clotures.simulation') }}" class="row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label">Journée comptable</label><input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-4"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Simuler la clôture</button></div>
        </form>
    </div></div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white fw-bold">Journées ouvertes</div><div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Date</th><th>Journaux non regroupés</th><th>Statut</th><th></th></tr></thead><tbody>
        @forelse($joursOuverts as $jour)<tr><td>{{ \Carbon\Carbon::parse($jour->date)->format('d/m/Y') }}</td><td>{{ $jour->total }}</td><td><span class="badge bg-info text-dark">Ouverte</span></td><td><a href="{{ route('parametres.clotures.simulation',['date'=>$jour->date]) }}" class="btn btn-sm btn-outline-primary">Simuler</a></td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted py-4">Aucune journée ouverte.</td></tr>@endforelse
        </tbody></table></div></div>

    <div class="card border-0 shadow-sm"><div class="card-header bg-dark text-white fw-bold">Historique des clôtures</div><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Journée</th><th>Numéro</th><th>Statut</th><th>Journaux</th><th>BEC</th><th>BSC</th><th>BRC</th><th>Écritures</th><th>Recettes</th><th>Dépenses</th><th>Rejets</th><th>Clôturée à</th><th>Utilisateur</th><th></th></tr></thead><tbody>
        @forelse($clotures as $cloture)<tr>
            <td>{{ $cloture->date_comptable->format('d/m/Y') }}</td><td class="fw-semibold">{{ $cloture->numero_cloture }}</td>
            <td><span class="badge bg-{{ $cloture->statut==='verifiee'?'success':($cloture->statut==='reouverte'?'warning':'primary') }}">{{ ucfirst($cloture->statut) }}</span></td>
            <td>{{ $cloture->total_journaux }}</td><td>{{ $cloture->entrees_count }}</td><td>{{ $cloture->sorties_count }}</td><td>{{ $cloture->brcs_count }}</td><td>{{ $cloture->total_ecritures }}</td>
            <td class="text-nowrap">{{ number_format($cloture->total_recettes_cdf,2,',',' ') }} CDF<br>{{ number_format($cloture->total_recettes_usd,2,',',' ') }} USD</td>
            <td class="text-nowrap">{{ number_format($cloture->total_depenses_cdf,2,',',' ') }} CDF<br>{{ number_format($cloture->total_depenses_usd,2,',',' ') }} USD</td>
            <td>{{ $cloture->nombre_journaux_rejetes }}</td><td>{{ $cloture->date_cloture?->format('H:i') }}</td><td>{{ trim(($cloture->clotureur?->prenom ?? '').' '.($cloture->clotureur?->nom ?? '')) ?: '—' }}</td>
            <td><a href="{{ route('parametres.clotures.show',$cloture) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
        </tr>@empty<tr><td colspan="14" class="text-center text-muted py-5">Aucune clôture enregistrée.</td></tr>@endforelse
        </tbody></table></div>@if($clotures->hasPages())<div class="card-footer bg-white">{{ $clotures->links() }}</div>@endif</div>
</div>
@endsection
