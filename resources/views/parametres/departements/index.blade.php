@extends('layouts.app')
@section('title', 'Départements et fonctions')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div><h3 class="fw-bold mb-1"><i class="bi bi-diagram-3 text-primary me-2"></i>Départements et fonctions</h3><p class="text-muted mb-0">Gérez séparément la structure et les postes des utilisateurs.</p></div>
        <a href="{{ route('parametres.parametre') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Paramètres</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body d-flex align-items-center gap-3"><span class="bg-primary bg-opacity-10 text-primary rounded-3 p-3"><i class="bi bi-building fs-4"></i></span><div><div class="fs-4 fw-bold">{{ $departements->count() }}</div><small class="text-muted">Départements</small></div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body d-flex align-items-center gap-3"><span class="bg-success bg-opacity-10 text-success rounded-3 p-3"><i class="bi bi-person-badge fs-4"></i></span><div><div class="fs-4 fw-bold">{{ $fonctions->count() }}</div><small class="text-muted">Fonctions</small></div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body d-flex align-items-center gap-3"><span class="bg-info bg-opacity-10 text-info rounded-3 p-3"><i class="bi bi-people fs-4"></i></span><div><div class="fs-4 fw-bold">{{ $users->count() }}</div><small class="text-muted">Utilisateurs</small></div></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-header bg-white py-3 fw-bold"><i class="bi bi-building text-primary me-2"></i>Départements</div><div class="card-body">
                <form method="POST" action="{{ route('parametres.departements.store') }}" class="d-flex gap-2 mb-3">@csrf
                    <input name="designation" value="{{ old('designation') }}" class="form-control" placeholder="Nouveau département" required>
                    <button class="btn btn-primary"><i class="bi bi-plus-circle"></i></button>
                </form>
                <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Désignation</th><th>Utilisateurs</th><th>États</th><th></th></tr></thead><tbody>
                    @forelse($departements as $departement)<tr>
                        <td><input form="departement-{{ $departement->id }}" name="designation" value="{{ $departement->designation }}" class="form-control form-control-sm" required></td>
                        <td>{{ $departement->users_count }}</td><td>{{ $departement->etat_besoins_count }}</td>
                        <td class="text-nowrap"><form id="departement-{{ $departement->id }}" method="POST" action="{{ route('parametres.departements.update', $departement) }}" class="d-inline">@csrf @method('PUT')<button class="btn btn-sm btn-primary"><i class="bi bi-check"></i></button></form> <form method="POST" action="{{ route('parametres.departements.destroy', $departement) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ce département ?')"><i class="bi bi-trash"></i></button></form></td>
                    </tr>@empty<tr><td colspan="4" class="text-center text-muted">Aucun département.</td></tr>@endforelse
                </tbody></table></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-header bg-white py-3 fw-bold"><i class="bi bi-person-badge text-success me-2"></i>Fonctions</div><div class="card-body">
                <form method="POST" action="{{ route('parametres.fonctions.store') }}" class="d-flex gap-2 mb-3">@csrf
                    <input name="designation" class="form-control" placeholder="Nouvelle fonction" required>
                    <button class="btn btn-primary"><i class="bi bi-plus-circle"></i></button>
                </form>
                <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Désignation</th><th>Utilisateurs</th><th></th></tr></thead><tbody>
                    @forelse($fonctions as $fonction)<tr>
                        <td><input form="fonction-{{ $fonction->id }}" name="designation" value="{{ $fonction->designation }}" class="form-control form-control-sm" required></td><td>{{ $fonction->users_count }}</td>
                        <td class="text-nowrap"><form id="fonction-{{ $fonction->id }}" method="POST" action="{{ route('parametres.fonctions.update', $fonction) }}" class="d-inline">@csrf @method('PUT')<button class="btn btn-sm btn-primary"><i class="bi bi-check"></i></button></form> <form method="POST" action="{{ route('parametres.fonctions.destroy', $fonction) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette fonction ?')"><i class="bi bi-trash"></i></button></form></td>
                    </tr>@empty<tr><td colspan="3" class="text-center text-muted">Aucune fonction.</td></tr>@endforelse
                </tbody></table></div>
            </div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4"><div class="card-header bg-white py-3"><div class="fw-bold"><i class="bi bi-people text-primary me-2"></i>Affectation des utilisateurs</div><small class="text-muted">Associez indépendamment un département et une fonction à chaque utilisateur.</small></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Utilisateur</th><th>Rôle</th><th>Département et fonction</th></tr></thead><tbody>
        @forelse($users as $agent)<tr><td><span class="fw-semibold">{{ $agent->prenom }} {{ $agent->nom }}</span><small class="d-block text-muted">{{ $agent->email }}</small></td><td><span class="badge bg-light text-dark border">{{ $agent->role?->designation ?? 'Sans rôle' }}</span></td><td><form method="POST" action="{{ route('parametres.utilisateurs.departement', $agent) }}" class="row g-2">@csrf @method('PATCH')
            <div class="col-md-5"><select name="departement_id" class="form-select"><option value="">Non affecté</option>@foreach($departements as $departement)<option value="{{ $departement->id }}" {{ $agent->departement_id === $departement->id ? 'selected' : '' }}>{{ $departement->designation }}</option>@endforeach</select></div>
            <div class="col-md-5"><select name="fonction_id" class="form-select"><option value="">Non affectée</option>@foreach($fonctions as $fonction)<option value="{{ $fonction->id }}" {{ $agent->fonction_id === $fonction->id ? 'selected' : '' }}>{{ $fonction->designation }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Affecter</button></div>
        </form></td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-4">Aucun utilisateur.</td></tr>@endforelse
    </tbody></table></div></div>
</div>
@endsection
