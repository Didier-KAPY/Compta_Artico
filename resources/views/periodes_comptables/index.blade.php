@extends('layouts.app')
@section('title', 'Périodes comptables')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><span class="text-primary small fw-bold text-uppercase">Contrôle comptable</span><h2 class="mb-0">Clôtures mensuelles et annuelles</h2></div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('parametres.periodes.imprimer') }}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i>Imprimer</a>
            <a href="{{ route('parametres.periodes.pdf') }}" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Télécharger PDF</a>
            <a href="{{ route('parametres.parametre') }}" class="btn btn-outline-secondary">Retour</a>
        </div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <h5 class="fw-bold">Fermer une période</h5>
        <form method="POST" action="{{ route('parametres.periodes.store') }}" class="row g-3 align-items-end" data-confirm="Confirmer le verrouillage de cette période ?">
            @csrf
            <div class="col-md-4"><label class="form-label">Type</label><select name="type" class="form-select" required><option value="mensuelle">Mensuelle</option><option value="annuelle">Annuelle</option></select></div>
            <div class="col-md-4"><label class="form-label">Mois de référence</label><input type="month" name="periode" value="{{ now()->format('Y-m') }}" class="form-control" required></div>
            <div class="col-md-4"><button class="btn btn-danger w-100" data-loading-text="Clôture..."><i class="bi bi-lock me-1"></i>Clôturer la période</button></div>
        </form>
    </div></div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-warning-subtle fw-bold"><i class="bi bi-unlock me-2"></i>Ouvrir une journée pour régularisation</div>
        <div class="card-body pb-2"><p class="text-muted mb-2">Utilisez cette action lorsqu’une saisie est bloquée par une clôture journalière. Le motif est obligatoire et l’opération est enregistrée dans l’audit.</p></div>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Journée</th><th>Clôture</th><th>Statut</th><th>Clôturée par</th><th>Régularisation</th></tr></thead>
            <tbody>@forelse($journeesCloturees as $cloture)<tr>
                <td>{{ $cloture->date_comptable->format('d/m/Y') }}</td><td>{{ $cloture->numero_cloture }}</td><td><span class="badge bg-primary">{{ ucfirst($cloture->statut) }}</span></td>
                <td>{{ trim(($cloture->clotureur?->prenom ?? '').' '.($cloture->clotureur?->nom ?? '')) ?: '—' }}</td>
                <td><form method="POST" action="{{ route('parametres.clotures.reouvrir', $cloture) }}" class="d-flex gap-2" data-confirm="Confirmer l’ouverture de cette journée pour régularisation ?">@csrf<input name="motif" class="form-control form-control-sm" placeholder="Motif de régularisation (10 caractères min.)" required minlength="10" maxlength="1000"><button class="btn btn-sm btn-warning text-nowrap"><i class="bi bi-unlock me-1"></i>Ouvrir</button></form></td>
            </tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">Aucune journée comptable clôturée.</td></tr>@endforelse</tbody>
        </table></div>
    </div>

    <div class="card border-0 shadow-sm"><div class="card-header bg-dark text-white fw-bold">Historique</div><div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Type</th><th>Début</th><th>Fin</th><th>Statut</th><th>Fermée le</th><th>Action</th></tr></thead>
        <tbody>@forelse($periodes as $periode)<tr>
            <td>{{ ucfirst($periode->type) }}</td><td>{{ $periode->date_debut->format('d/m/Y') }}</td><td>{{ $periode->date_fin->format('d/m/Y') }}</td>
            <td><span class="badge bg-{{ $periode->statut === 'fermee' ? 'danger' : 'warning' }}">{{ ucfirst($periode->statut) }}</span></td><td>{{ $periode->fermee_le?->format('d/m/Y H:i') }}</td>
            <td>@if($periode->statut === 'fermee')<form method="POST" action="{{ route('parametres.periodes.reouvrir', $periode) }}" class="d-flex gap-2" data-confirm="Confirmer la réouverture ?">@csrf<input name="motif" class="form-control form-control-sm" placeholder="Motif (10 caractères min.)" required minlength="10"><button class="btn btn-sm btn-warning">Réouvrir</button></form>@else<span class="text-muted small">Réouverte</span>@endif</td>
        </tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Aucune période clôturée.</td></tr>@endforelse</tbody>
    </table></div>@if($periodes->hasPages())<div class="card-footer">{{ $periodes->links() }}</div>@endif</div>
</div>
@endsection
