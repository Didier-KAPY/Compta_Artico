@extends('layouts.app')
@section('title', 'Imputation comptable')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><span class="text-uppercase text-muted small fw-bold">Imputation comptable</span><h3 class="mb-0">{{ $ecriture->piece ?: 'Écriture #'.$ecriture->id }}</h3></div>
        <a href="{{ route('ecritures.liste') }}" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
    </div>
    @include('partials.document-navigation')
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if(!$estImpute && abs((float)$ecriture->debit_cdf - (float)$ecriture->credit_cdf) > 0.005)
        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong>Écriture non équilibrée.</strong><br>
                @if($montantTva > 0)
                    <span>La TVA et la contrepartie ne sont pas encore affichées comme lignes comptables.</span>
                    <div class="mt-2 d-flex flex-wrap gap-3">
                        <span><strong>Montant TVA inclus :</strong> {{ number_format($montantTva, 2, ',', ' ') }} CDF</span>
                        <span><strong>Contrepartie à imputer :</strong> {{ number_format($montantContrepartie, 2, ',', ' ') }} CDF</span>
                    </div>
                @else
                    <span>La contrepartie n’est pas encore affichée comme ligne comptable.</span>
                    <div class="mt-2"><strong>Contrepartie à imputer :</strong> {{ number_format($montantContrepartie, 2, ',', ' ') }} CDF</div>
                @endif
                <small>Effectuez l’imputation pour créer la contrepartie et équilibrer cette écriture.</small>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white"><strong>{{ $estImpute ? ($montantTva > 0 ? 'Écriture imputée — contreparties et TVA' : 'Écriture imputée — contreparties') : 'Information à imputer' }}</strong></div>
        <div class="table-responsive"><table class="table table-bordered align-middle mb-0">
            <thead class="table-light"><tr><th>Compte</th><th>Libellé du bon</th><th class="text-end">Débit CDF</th><th class="text-end">Crédit CDF</th><th>Statut</th></tr></thead>
            <tbody>
            @foreach($estImpute ? $ecrituresReference : collect([$ecriture]) as $ligne)
                <tr>
                    <td>{{ $ligne->compte?->compte ?? '—' }} — {{ $ligne->compte?->designation ?? '—' }}</td>
                    <td>{{ $ligne->journal?->description ?: ($ligne->libelle ?: '—') }} @if($estImpute && str_starts_with(mb_strtolower(trim((string)$ligne->libelle)), 'tva'))<span class="badge bg-info text-dark ms-1">TVA imputée</span>@endif</td>
                    <td class="text-end">{{ number_format($ligne->debit_cdf, 2, ',', ' ') }}</td>
                    <td class="text-end">{{ number_format($ligne->credit_cdf, 2, ',', ' ') }}</td>
                    <td><span class="badge {{ $ligne->statut === 'Validé' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $ligne->statut }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </div>

    @unless($estImpute)
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <strong>Imputer et valider</strong>
            <button type="button" id="ajouterImputation" class="btn btn-sm btn-light"><i class="bi bi-plus-circle me-1"></i>Ajouter une ligne</button>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('ecritures.valider', $ecriture) }}" enctype="multipart/form-data">
                @csrf

                <div id="lignesImputation" data-max="20" data-expected="{{ number_format($montantTotalAImputer, 2, '.', '') }}">
                    @php($anciennes = old('imputations', [['liste_des_comptes_id' => null, 'montant' => null]]))
                    @foreach($anciennes as $index => $ancienne)
                    <div class="ligne-imputation border rounded p-3 mb-3" data-index="{{ $index }}">
                        <div class="d-flex justify-content-between mb-2"><strong>Imputation <span class="numero-ligne">{{ $index + 1 }}</span></strong><button type="button" class="btn btn-sm btn-outline-danger supprimer-ligne @if($index === 0) d-none @endif"><i class="bi bi-trash"></i></button></div>
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-8">
                                <label class="form-label fw-bold">Compte de la ligne opposée <span class="text-danger">*</span></label>
                                <select name="imputations[{{ $index }}][liste_des_comptes_id]" class="form-select compte-search" required>
                                    <option value="">Rechercher un compte ou un libellé</option>
                                    @foreach($comptes as $compte)<option value="{{ $compte->id }}" @selected((string)($ancienne['liste_des_comptes_id'] ?? '') === (string)$compte->id)>{{ $compte->compte }} — {{ $compte->designation }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label fw-bold">Montant <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="imputations[{{ $index }}][montant]" class="form-control text-end" min="0.01" step="0.01" value="{{ $ancienne['montant'] ?? '' }}" placeholder="Montant" required>
                                    <span class="input-group-text">CDF</span>
                                </div>
                            </div>

                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="border rounded p-3 mb-3">
                    <label class="form-label fw-bold">Pièce justificative @if($pieceObligatoire)<span class="text-danger">*</span>@endif</label>
                    <input type="file" name="piece_justificative" class="form-control" accept=".pdf,.jpg,.jpeg,.png" @required($pieceObligatoire)>
                    <small class="text-muted">Cette pièce unique sera liée à toutes les lignes par leur même référence.</small>
                </div>
                <small class="text-muted d-block">Chaque ligne ajoutée correspond à une contrepartie existante, dans le sens inverse de l’écriture affichée.</small>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-4">
                    <span id="etatEquilibre" class="text-danger small fw-semibold">Saisissez les montants pour équilibrer l’écriture.</span>
                    <button type="submit" id="validerImputation" class="btn btn-success" data-confirm="Valider cette imputation ?" disabled><i class="bi bi-check-circle me-1"></i>Valider l’imputation</button>
                </div>
            </form>
        </div>
    </div>
    @endunless
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('lignesImputation');
    const addButton = document.getElementById('ajouterImputation');
    const validateButton = document.getElementById('validerImputation');
    const balanceState = document.getElementById('etatEquilibre');
    if (!container || !addButton || !validateButton || !balanceState) return;
    const max = Number(container.dataset.max || 1);
    const expected = Number(container.dataset.expected || 0);
    const refresh = () => {
        const rows = [...container.querySelectorAll('.ligne-imputation')];
        rows.forEach((row, index) => {
            row.dataset.index = index;
            row.querySelector('.numero-ligne').textContent = index + 1;
            row.querySelectorAll('[name]').forEach(field => field.name = field.name.replace(/imputations\[\d+\]/, `imputations[${index}]`));
            row.querySelector('.supprimer-ligne').classList.toggle('d-none', rows.length === 1);
        });
        addButton.disabled = rows.length >= max;

        const total = rows.reduce((sum, row) => {
            const field = row.querySelector('input[type="number"]');
            return sum + (Number(field?.value || 0) || 0);
        }, 0);
        const difference = Math.abs(expected - total);
        const balanced = expected > 0 && difference <= 0.005;
        validateButton.disabled = !balanced;
        balanceState.classList.toggle('text-danger', !balanced);
        balanceState.classList.toggle('text-success', balanced);
        balanceState.textContent = balanced
            ? 'Écriture équilibrée : vous pouvez valider.'
            : 'Écart avec la contrepartie : ' + difference.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' CDF.';
    };
    addButton.addEventListener('click', function () {
        const rows = container.querySelectorAll('.ligne-imputation');
        if (rows.length >= max) return;
        const clone = rows[0].cloneNode(true);
        clone.querySelectorAll('.select2-container').forEach(element => element.remove());
        clone.querySelectorAll('select').forEach(field => {
            field.value = '';
            field.classList.remove('select2-hidden-accessible');
            field.removeAttribute('data-select2-id');
            field.removeAttribute('aria-hidden');
            field.removeAttribute('tabindex');
            field.removeAttribute('style');
            field.querySelectorAll('option').forEach(option => option.removeAttribute('data-select2-id'));
        });
        clone.querySelectorAll('input[type="file"], input[type="number"]').forEach(field => field.value = '');
        container.appendChild(clone);
        refresh();
        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery(clone.querySelectorAll('select.compte-search')).select2({
                width: '100%',
                placeholder: 'Rechercher un compte ou un libellé',
                allowClear: true
            });
        }
    });
    container.addEventListener('input', function (event) {
        if (event.target.matches('input[type="number"]')) refresh();
    });
    container.addEventListener('click', function (event) {
        const button = event.target.closest('.supprimer-ligne');
        if (!button) return;
        button.closest('.ligne-imputation').remove();
        refresh();
    });
    refresh();
});
</script>
@endpush