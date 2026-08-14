@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><span class="text-uppercase text-muted small fw-bold">Traçabilité</span><h3 class="mb-0"><i class="bi bi-trash3 me-2"></i>Corbeille financière</h3></div>
        <div class="d-flex gap-2">
            @if($trashCount > 0)<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalViderCorbeille"><i class="bi bi-trash3-fill me-1"></i>Vider la corbeille</button>@endif
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
        </div>
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

    @if($trashCount > 0)
    <div class="modal fade" id="modalViderCorbeille" tabindex="-1" aria-labelledby="titreViderCorbeille" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <form method="POST" action="{{ route('corbeille.empty') }}">@csrf @method('DELETE')
                <div class="modal-header"><h5 class="modal-title" id="titreViderCorbeille"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Vider la corbeille</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">
                    <div class="alert alert-danger">Cette action supprimera définitivement {{ $trashCount }} document{{ $trashCount > 1 ? 's' : '' }} de la corbeille et ne pourra pas être annulée.</div>
                    <label class="form-label">Motif de la suppression définitive</label><textarea name="motif" minlength="10" maxlength="1000" required class="form-control mb-3"></textarea>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="confirmation_comptable" value="1" id="confirmationVider" required><label class="form-check-label" for="confirmationVider">Je confirme la suppression définitive de tous ces documents.</label></div>
                    <label class="form-label">Saisissez <strong>VIDER LA CORBEILLE</strong></label><input name="phrase_confirmation" class="form-control" autocomplete="off" required>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn btn-danger"><i class="bi bi-trash3-fill me-1"></i>Supprimer définitivement</button></div>
            </form>
        </div></div>
    </div>
    @endif
</div>
@endsection
