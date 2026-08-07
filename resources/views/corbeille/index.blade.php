@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><span class="text-uppercase text-muted small fw-bold">Traçabilité</span><h3 class="mb-0"><i class="bi bi-trash3 me-2"></i>Corbeille financière</h3></div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="GET" class="card border-0 shadow-sm mb-4"><div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">Module</label><select name="module" class="form-select"><option value="">Tous</option>@foreach($modules as $module)<option value="{{ $module }}" @selected(request('module')===$module)>{{ ucfirst(str_replace('-', ' ', $module)) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Référence</label><input name="reference" value="{{ request('reference') }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Utilisateur (ID)</label><input type="number" name="utilisateur" value="{{ request('utilisateur') }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Date suppression</label><input type="date" name="date_suppression" value="{{ request('date_suppression') }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Ancien statut</label><select name="statut" class="form-select"><option value="">Tous</option>@foreach(['En attente','Validé','Rejeté'] as $statut)<option @selected(request('statut')===$statut)>{{ $statut }}</option>@endforeach</select></div>
        <div class="col-12 text-end"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filtrer</button></div>
    </div></form>

    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Document</th><th>Référence</th><th>Ancien statut</th><th>Supprimé par</th><th>Date</th><th>Motif</th><th></th></tr></thead>
        <tbody>@forelse($documents as $item)<tr>
            <td>{{ $item['type'] }}</td><td class="fw-semibold">{{ $item['reference'] ?: '—' }}</td><td><span class="badge bg-secondary">{{ $item['statut'] }}</span></td>
            <td>{{ $item['audit']?->user ? trim($item['audit']->user->prenom.' '.$item['audit']->user->nom) : 'Utilisateur #'.$item['supprime_par'] }}</td>
            <td>{{ $item['deleted_at']?->format('d/m/Y H:i') }}</td><td>{{ \Illuminate\Support\Str::limit($item['motif'], 60) }}</td>
            <td><a href="{{ route('corbeille.show', [$item['module'],$item['id']]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Voir</a></td>
        </tr>@empty<tr><td colspan="7" class="text-center text-muted py-5">La corbeille est vide.</td></tr>@endforelse</tbody>
    </table></div></div>
</div>
@endsection
