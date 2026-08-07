@can('deleteFinancialDocument')
<div class="modal fade" id="modalSuppressionDocument" tabindex="-1" aria-labelledby="titreSuppressionDocument" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><div class="modal-content border-0 shadow-lg" style="max-height:calc(100vh - 2rem)">
        <div class="modal-header border-0 pb-0"><div class="d-flex align-items-center gap-3"><span class="d-grid place-items-center rounded-circle bg-danger-subtle text-danger" style="width:48px;height:48px"><i class="bi bi-exclamation-triangle-fill fs-4"></i></span><div><h5 class="modal-title fw-bold" id="titreSuppressionDocument">Suppression sécurisée</h5><small class="text-muted">{{ $documentType }} — {{ $documentReference ?: 'Sans référence' }}</small></div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
        <form method="POST" action="{{ $deleteRoute }}">@csrf @method('DELETE')
            <div class="modal-body py-4">
                <div class="row g-3 mb-3"><div class="col-md-6"><div class="p-3 rounded bg-light border"><small class="text-muted d-block">Document</small><strong>{{ $documentType }}</strong><div>{{ $documentReference ?: '—' }}</div></div></div><div class="col-md-6"><div class="p-3 rounded bg-light border"><small class="text-muted d-block">Statut actuel</small><span class="badge {{ $documentStatus === 'Validé' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $documentStatus }}</span></div></div></div>
                @if($documentStatus === 'Validé')<div class="alert alert-danger"><i class="bi bi-shield-exclamation me-2"></i><strong>Attention :</strong> ce document est déjà validé. Sa suppression peut affecter la traçabilité comptable.</div>@endif
                <div class="mb-3"><label class="form-label fw-semibold">Dépendances détectées</label><div class="border rounded p-3 bg-light">@forelse($suppressionDependencies as $dependency)<div class="d-flex justify-content-between gap-3 py-1"><span>{{ $dependency['type'] }} — <strong>{{ $dependency['reference'] }}</strong></span><span class="badge bg-secondary">{{ $dependency['statut'] }}</span></div>@empty<span class="text-muted">Aucune dépendance active.</span>@endforelse</div></div>
                <div class="mb-3"><label for="motifSuppression" class="form-label fw-semibold">Motif de la suppression <span class="text-danger">*</span></label><textarea id="motifSuppression" name="motif" class="form-control" rows="4" minlength="10" maxlength="1000" required placeholder="Expliquez précisément la raison (10 à 1000 caractères).">{{ old('motif') }}</textarea><div class="form-text">Entre 10 et 1000 caractères.</div></div>
                <div class="mb-3"><label class="form-label fw-semibold">Stratégie</label><select name="strategie" class="form-select" required><option value="individuelle">Suppression individuelle</option>@if(count($suppressionDependencies))<option value="cascade">Cascade contrôlée ({{ count($suppressionDependencies) }} dépendance(s))</option>@endif</select><div class="form-text">La suppression individuelle sera refusée si une dépendance active existe.</div></div>
                <label class="form-check p-3 border rounded"><input type="checkbox" name="confirmation_comptable" value="1" required class="form-check-input ms-0 me-2"><span class="form-check-label fw-semibold">Je confirme avoir vérifié les conséquences comptables de cette suppression.</span></label>
            </div>
            <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Annuler</button><button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Confirmer la suppression</button></div>
        </form>
    </div></div>
</div>
<style>
    #modalSuppressionDocument { z-index: 20000 !important; }
    body:has(#modalSuppressionDocument.show) .modal-backdrop { z-index: 19990 !important; }
    #modalSuppressionDocument .modal-body {
        max-height: calc(100vh - 190px);
        overflow-y: scroll;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #eef2f7;
    }
    #modalSuppressionDocument .modal-body::-webkit-scrollbar { width: 9px; }
    #modalSuppressionDocument .modal-body::-webkit-scrollbar-track { background: #eef2f7; border-radius: 10px; }
    #modalSuppressionDocument .modal-body::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 10px; border: 2px solid #eef2f7; }
    #modalSuppressionDocument .modal-body::-webkit-scrollbar-thumb:hover { background: #64748b; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const suppressionModal = document.getElementById('modalSuppressionDocument');
    if (suppressionModal && suppressionModal.parentElement !== document.body) {
        document.body.appendChild(suppressionModal);
    }
});
</script>
@endcan
