@extends('layouts.app')
@section('title', $cloture->numero_cloture)
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><span class="text-primary small fw-bold text-uppercase">Chaîne documentaire</span><h2 class="mb-0">{{ $cloture->numero_cloture }}</h2><div class="text-muted">Journée du {{ $cloture->date_comptable->format('d/m/Y') }} — révision {{ $cloture->revision }}</div></div><div class="d-flex gap-2"><a href="{{ route('parametres.audit.index',['reference'=>$cloture->numero_cloture]) }}" class="btn btn-outline-dark"><i class="bi bi-shield-check me-1"></i>Audits</a><a href="{{ route('parametres.clotures.index') }}" class="btn btn-outline-secondary">Retour</a></div></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small>Statut</small><div><span class="badge bg-{{ $cloture->statut==='verifiee'?'success':($cloture->statut==='reouverte'?'warning':'primary') }}">{{ ucfirst($cloture->statut) }}</span></div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small>Journaux</small><div class="h4 mb-0">{{ $cloture->total_journaux }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small>Écritures</small><div class="h4 mb-0">{{ $cloture->total_ecritures }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small>Clôturée par</small><div class="fw-bold">{{ trim(($cloture->clotureur?->prenom ?? '').' '.($cloture->clotureur?->nom ?? '')) ?: '—' }}</div></div></div></div>
    </div>
    @foreach([['Bons d’entrée',$cloture->entrees,'entree-caisses.show'],['Bons de sortie',$cloture->sorties,'sortie-caisses.show'],['BRC',$cloture->brcs,'brc.show']] as [$titre,$documents,$route])
    <div class="card border-0 shadow-sm mb-4"><div class="card-header bg-dark text-white"><strong>{{ $titre }}</strong></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Document</th><th>Monnaie</th><th>Statut</th><th>Journaux</th><th>Écritures</th><th></th></tr></thead><tbody>
        @forelse($documents as $document)<tr><td class="fw-semibold">{{ $document->numero ?? $document->reference }}</td><td>{{ $document->monnaie }}</td><td>{{ $document->statut }}</td><td>{{ $document->journaux->count() }}</td><td>{{ $document->journaux->sum(fn($j)=>$j->ecritures->count()) }}</td><td><a href="{{ route($route,$document) }}" class="btn btn-sm btn-outline-primary">Consulter</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted">Aucun document.</td></tr>@endforelse
    </tbody></table></div></div>
    @endforeach
    <div class="d-flex flex-wrap gap-2">
        @if($cloture->statut==='cloturee')<form method="POST" action="{{ route('parametres.clotures.verifier',$cloture) }}">@csrf<button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Vérifier la clôture</button></form>@endif
        @if(in_array($cloture->statut,['cloturee','verifiee']))<button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalReouverture"><i class="bi bi-unlock me-1"></i>Rouvrir</button>@endif
    </div>
</div>
<div class="modal fade" id="modalReouverture" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('parametres.clotures.reouvrir',$cloture) }}">@csrf<div class="modal-header"><h5 class="modal-title">Rouvrir {{ $cloture->numero_cloture }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-warning">La réouverture sera refusée si la chaîne contient un document ou une écriture validée.</div><label class="form-label">Motif détaillé</label><textarea name="motif" class="form-control" minlength="10" required rows="4"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button><button class="btn btn-warning">Confirmer la réouverture</button></div></form></div></div></div>
@endsection
