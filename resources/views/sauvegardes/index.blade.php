@extends('layouts.app')
@section('title', 'Sauvegardes')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div><span class="text-primary small fw-bold text-uppercase">Sécurité des données</span><h2 class="mb-1">Import et export MySQL</h2><p class="text-muted mb-0">Sauvegardez toute la base ou restaurez-la depuis un fichier SQL.</p></div>
        <a href="{{ route('parametres.parametre') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="row g-4 mb-4">
        <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4">
            <div class="d-flex align-items-start gap-3 mb-3"><span class="rounded-circle bg-primary-subtle text-primary p-3"><i class="bi bi-database-down fs-4"></i></span><div><h5 class="mb-1">Exporter la base</h5><p class="text-muted mb-0">Crée une copie complète que vous pourrez télécharger ci-dessous.</p></div></div>
            <form method="POST" action="{{ route('parametres.sauvegardes.store') }}">@csrf<button class="btn btn-primary" data-loading-text="Export en cours..."><i class="bi bi-download me-1"></i>Créer une sauvegarde</button></form>
        </div></div></div>
        <div class="col-lg-6"><div class="card border-danger-subtle shadow-sm h-100"><div class="card-body p-4">
            <div class="d-flex align-items-start gap-3 mb-3"><span class="rounded-circle bg-danger-subtle text-danger p-3"><i class="bi bi-database-up fs-4"></i></span><div><h5 class="mb-1">Importer une base</h5><p class="text-muted mb-0">Le fichier SQL remplacera les données actuelles. Taille maximale : 100 Mo.</p></div></div>
            <form method="POST" enctype="multipart/form-data" action="{{ route('parametres.sauvegardes.import') }}" data-confirm="Cette importation remplacera les données actuelles. Continuer ?">@csrf
                <div class="mb-3"><label for="fichierImport" class="form-label">Fichier SQL</label><input id="fichierImport" type="file" name="fichier" accept=".sql,application/sql,text/plain" class="form-control @error('fichier') is-invalid @enderror" required>@error('fichier')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label for="passwordImport" class="form-label">Votre mot de passe</label><input id="passwordImport" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="form-check mb-3"><input id="confirmationImport" class="form-check-input" type="checkbox" name="confirmation" value="1" required><label class="form-check-label" for="confirmationImport">Je confirme le remplacement de la base actuelle.</label></div>
                <button class="btn btn-danger" data-loading-text="Import en cours..."><i class="bi bi-upload me-1"></i>Importer et restaurer</button>
            </form>
        </div></div></div>
    </div>

    <div class="card border-0 shadow-sm"><div class="card-header bg-white py-3"><strong>Sauvegardes disponibles</strong></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Fichier</th><th>Taille</th><th>Actions</th></tr></thead><tbody>
        @forelse($fichiers as $f)<tr><td><i class="bi bi-file-earmark-code text-primary me-2"></i>{{ basename($f) }}</td><td>{{ number_format(Storage::disk('local')->size($f)/1024,1,',',' ') }} Ko</td><td><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-primary" href="{{ route('parametres.sauvegardes.download',basename($f)) }}" data-no-loading><i class="bi bi-download me-1"></i>Télécharger</a><form method="POST" action="{{ route('parametres.sauvegardes.restore') }}" class="d-flex flex-wrap gap-2" data-confirm="Cette restauration remplacera les données actuelles. Continuer ?">@csrf<input type="hidden" name="fichier" value="{{ basename($f) }}"><input type="hidden" name="confirmation" value="1"><input type="password" name="password" class="form-control form-control-sm" style="width:180px" placeholder="Votre mot de passe" autocomplete="current-password" required><button class="btn btn-sm btn-outline-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Restaurer</button></form></div></td></tr>
        @empty<tr><td colspan="3" class="text-center text-muted py-5"><i class="bi bi-database-x d-block fs-2 mb-2"></i>Aucune sauvegarde disponible.</td></tr>@endforelse
    </tbody></table></div></div>
</div>
@endsection
