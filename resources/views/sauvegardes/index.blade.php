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
            <form method="POST" action="{{ route('parametres.sauvegardes.store') }}">@csrf<button class="btn btn-primary" data-loading-text="Export en cours..."><i class="bi bi-download me-1"></i>Créer une sauvegarde SQL</button></form>
            <form method="POST" action="{{ route('parametres.sauvegardes.export-package') }}" class="mt-2">@csrf<button class="btn btn-outline-primary" data-loading-text="Préparation du dossier..."><i class="bi bi-folder-symlink me-1"></i>Exporter le dossier de travail</button></form>
            <small class="text-muted d-block mt-2">Inclut la base, les images, JPG/PNG et PDF des pièces justificatives.</small>
        </div></div></div>
        <div class="col-lg-6"><div class="card border-danger-subtle shadow-sm h-100"><div class="card-body p-4">
            <div class="d-flex align-items-start gap-3 mb-3"><span class="rounded-circle bg-danger-subtle text-danger p-3"><i class="bi bi-database-up fs-4"></i></span><div><h5 class="mb-1">Importer une base</h5><p class="text-muted mb-0">Le fichier SQL remplacera les données actuelles. Taille maximale : 100 Mo.</p></div></div>
            <form id="databaseImportForm" method="POST" enctype="multipart/form-data" action="{{ route('parametres.sauvegardes.import') }}" onsubmit="return false;" data-no-loading>@csrf
                <div class="mb-3"><label for="fichierImport" class="form-label">Fichier SQL</label><input id="fichierImport" type="file" name="fichier" accept=".sql,application/sql,text/plain" class="form-control @error('fichier') is-invalid @enderror" required>@error('fichier')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label for="passwordImport" class="form-label">Votre mot de passe</label><input id="passwordImport" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="form-check mb-3"><input id="confirmationImport" class="form-check-input" type="checkbox" name="confirmation" value="1" required><label class="form-check-label" for="confirmationImport">Je confirme le remplacement de la base actuelle.</label></div>
                <div id="databaseImportProgress" class="progress mb-3 d-none" role="progressbar" aria-label="Progression de l'import"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%">0 %</div></div>
                <div id="databaseImportMessage" class="alert d-none"></div>
                <button type="button" class="btn btn-danger" data-import-trigger><i class="bi bi-upload me-1"></i>Importer et restaurer</button>
            </form>
            <hr>
            <form id="workspaceImportForm" method="POST" enctype="multipart/form-data" action="{{ route('parametres.sauvegardes.import-package') }}" onsubmit="return false;" data-no-loading>@csrf
                <div class="mb-3"><label for="workspaceImport" class="form-label">Dossier de travail (.zip)</label><input id="workspaceImport" type="file" name="fichier" accept=".zip,application/zip" class="form-control" required><small class="text-muted">Paquet exporté par Compta Artico, maximum 500 Mo.</small></div>
                <div class="mb-3"><label for="workspacePassword" class="form-label">Votre mot de passe</label><input id="workspacePassword" type="password" name="password" class="form-control" autocomplete="current-password" required></div>
                <div class="form-check mb-3"><input id="workspaceConfirmation" class="form-check-input" type="checkbox" name="confirmation" value="1" required><label class="form-check-label" for="workspaceConfirmation">Je confirme le remplacement de la base et des fichiers.</label></div>
                <div id="workspaceImportProgress" class="progress mb-3 d-none" role="progressbar" aria-label="Progression de l'import"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%">0 %</div></div>
                <div id="workspaceImportMessage" class="alert d-none"></div>
                <button type="button" class="btn btn-danger" data-import-trigger><i class="bi bi-folder-plus me-1"></i>Importer le dossier de travail</button>
            </form>
        </div></div></div>
    </div>

    <div class="card border-0 shadow-sm"><div class="card-header bg-white py-3"><strong>Sauvegardes disponibles</strong></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Fichier</th><th>Taille</th><th>Actions</th></tr></thead><tbody>
        @forelse($fichiers as $f)<tr><td><i class="bi bi-file-earmark-code text-primary me-2"></i>{{ basename($f) }}</td><td>{{ number_format(Storage::disk('local')->size($f)/1024,1,',',' ') }} Ko</td><td><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-primary" href="{{ route('parametres.sauvegardes.download',basename($f)) }}" data-no-loading><i class="bi bi-download me-1"></i>Télécharger</a><form method="POST" action="{{ route('parametres.sauvegardes.restore') }}" class="d-flex flex-wrap gap-2" data-confirm="Cette restauration remplacera les données actuelles. Continuer ?">@csrf<input type="hidden" name="fichier" value="{{ basename($f) }}"><input type="hidden" name="confirmation" value="1"><input type="password" name="password" class="form-control form-control-sm" style="width:180px" placeholder="Votre mot de passe" autocomplete="current-password" required><button class="btn btn-sm btn-outline-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Restaurer</button></form></div></td></tr>
        @empty<tr><td colspan="3" class="text-center text-muted py-5"><i class="bi bi-database-x d-block fs-2 mb-2"></i>Aucune sauvegarde disponible.</td></tr>@endforelse
    </tbody></table></div></div>
</div>
<script>
function configureChunkedImport(formId, progressId, messageId, extension, maxSize, confirmationText) {
const configuredForm = document.getElementById(formId);
configuredForm?.addEventListener('submit', event => event.preventDefault());
configuredForm?.querySelector('[data-import-trigger]')?.addEventListener('click', async function () {
    if (!window.confirm(confirmationText)) return;
    const form = configuredForm;
    const file = form.querySelector('[name="fichier"]').files[0];
    const password = form.querySelector('[name="password"]').value;
    const confirmation = form.querySelector('[name="confirmation"]').checked;
    const token = form.querySelector('[name="_token"]').value;
    const progress = document.getElementById(progressId);
    const bar = progress.querySelector('.progress-bar');
    const message = document.getElementById(messageId);
    const button = form.querySelector('[data-import-trigger]');
    if (!file || !password || !confirmation) return;
    if (!file.name.toLowerCase().endsWith('.' + extension)) return showError('Le fichier doit être au format .' + extension + '.');
    if (file.size > maxSize) return showError('Le fichier dépasse la taille maximale autorisée.');

    const chunkSize = 4 * 1024 * 1024;
    const total = Math.ceil(file.size / chunkSize);
    button.disabled = true;
    progress.classList.remove('d-none');
    message.classList.add('d-none');

    try {
        const started = await send('{{ route('parametres.sauvegardes.import.init') }}', values({nom:file.name, taille:file.size, nombre_blocs:total}));
        for (let index = 0; index < total; index++) {
            const payload = values({upload_id:started.upload_id, index:index});
            payload.append('bloc', file.slice(index * chunkSize, Math.min(file.size, (index + 1) * chunkSize)), 'bloc.part');
            await send('{{ route('parametres.sauvegardes.import.chunk') }}', payload);
            const percent = Math.round(((index + 1) / total) * 90);
            bar.style.width = percent + '%'; bar.textContent = percent + ' %';
        }
        bar.style.width = '95%'; bar.textContent = 'Restauration de la base…';
        const finished = await send('{{ route('parametres.sauvegardes.import.finish') }}', values({upload_id:started.upload_id, password:password, confirmation:'1'}));
        bar.style.width = '100%'; bar.textContent = '100 %';
        message.className = 'alert alert-success'; message.textContent = finished.message;
        window.setTimeout(() => window.location.assign('{{ route('parametres.sauvegardes.index') }}'), 1500);
    } catch (error) {
        showError(error.message || 'L’import a échoué.');
        button.disabled = false;
    }

    function values(items) { const data = new FormData(); data.append('_token', token); Object.entries(items).forEach(([key,value]) => data.append(key,value)); return data; }
    async function send(url, data) {
        const response = await fetch(url, {method:'POST', body:data, headers:{'Accept':'application/json','X-CSRF-TOKEN':token}});
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Erreur serveur (' + response.status + ').');
        return result;
    }
    function showError(text) { message.className = 'alert alert-danger'; message.textContent = text; }
});
}
configureChunkedImport('databaseImportForm', 'databaseImportProgress', 'databaseImportMessage', 'sql', 100 * 1024 * 1024, 'Cette importation remplacera les données actuelles. Continuer ?');
configureChunkedImport('workspaceImportForm', 'workspaceImportProgress', 'workspaceImportMessage', 'zip', 500 * 1024 * 1024, 'Le dossier remplacera la base et les fichiers actuels. Continuer ?');
</script>
@endsection
