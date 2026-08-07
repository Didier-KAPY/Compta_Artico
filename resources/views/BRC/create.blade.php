@extends('layouts.app')

@section('content')
<div class="container-fluid mt-3">
<div class="card shadow-sm border-0">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-file-earmark-plus me-2"></i>Nouveau BRC</strong>
        <a href="{{ route('brc.index') }}" class="btn btn-outline-light btn-sm">Retour</a>
    </div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="alert alert-light border py-2">
            @if($taux)1 USD = <strong>{{ number_format($taux->taux_de_change, 2, ',', ' ') }} CDF</strong>@else<span class="text-warning">Aucun taux de change configuré pour les USD.</span>@endif
        </div>
        <form method="POST" action="{{ route('brc.store') }}">@csrf
            <div class="row g-2 mb-3">
                <div class="col-md-2"><label class="small fw-bold">Date *</label><input type="date" name="date" class="form-control form-control-sm" value="{{ old('date', now()->toDateString()) }}" required></div>
                <div class="col-md-4"><label class="small fw-bold">Journal *</label><select name="journal_type_id" class="form-select form-select-sm journal-search" required><option value="">Choisir</option>@foreach($journaux as $journal)<option value="{{ $journal->id }}" @selected((string)old('journal_type_id') === (string)$journal->id)>{{ $journal->code }} — {{ $journal->compte?->compte }} - {{ $journal->compte?->designation }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="small fw-bold">Monnaie *</label><select name="monnaie" id="monnaie" class="form-select form-select-sm"><option value="CDF" @selected(old('monnaie', 'CDF') === 'CDF')>CDF</option><option value="USD" @selected(old('monnaie') === 'USD')>USD</option></select></div>
                <div class="col-md-4"><label class="small fw-bold">Sens du compte du journal *</label><select name="sens" class="form-select form-select-sm"><option value="debit" @selected(old('sens') === 'debit')>Débit</option><option value="credit" @selected(old('sens') === 'credit')>Crédit</option></select><small class="text-muted">Les imputations prennent le sens opposé.</small></div>
            </div>
            <div class="d-flex justify-content-between mb-2"><h6><i class="bi bi-calculator me-1"></i>Lignes d’imputation</h6><button type="button" id="ajouterLigne" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Ajouter une ligne</button></div>
            <div class="table-responsive"><table class="table table-sm table-bordered" id="tableLignes">
                <thead class="table-light"><tr><th width="35%">Compte</th><th>Libellé</th><th width="20%">Montant</th><th width="5%">Action</th></tr></thead>
                <tbody><tr>
                    <td><select name="lignes[0][compte_id]" class="form-select form-select-sm compte-search" required><option value="">Choisir compte</option>@foreach($comptes as $compte)<option value="{{ $compte->id }}">{{ $compte->compte }} - {{ $compte->designation }}</option>@endforeach</select></td>
                    <td><input name="lignes[0][libelle]" class="form-control form-control-sm" required></td>
                    <td><input type="number" name="lignes[0][montant]" class="form-control form-control-sm montant" min="0.01" step="0.01" value="0" required></td>
                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm supprimer"><i class="bi bi-trash"></i></button></td>
                </tr></tbody>
                <tfoot class="table-light"><tr><th colspan="2">TOTAL</th><th id="totalMontant">0.00 CDF</th><th></th></tr></tfoot>
            </table></div>
            <div class="text-end"><button class="btn btn-success"><i class="bi bi-save me-1"></i>Enregistrer le BRC</button></div>
        </form>
    </div>
</div></div>
@endsection

@section('scripts')
<script>
let ligneIndex = 1;
const optionsComptes = @json($comptes->map(fn($c) => ['id' => $c->id, 'texte' => $c->compte.' - '.$c->designation])->values());
function activerRecherche(selecteur, placeholder) { $(selecteur).each(function(){ if(!$(this).hasClass('select2-hidden-accessible')) $(this).select2({width:'100%', placeholder, allowClear:true}); }); }
function calculerTotal(){ let total=0; document.querySelectorAll('.montant').forEach(el => total += Number(el.value)||0); document.getElementById('totalMontant').textContent=total.toFixed(2)+' '+document.getElementById('monnaie').value; }
activerRecherche('.journal-search','Rechercher un journal'); activerRecherche('.compte-search','Rechercher un compte'); calculerTotal();
document.getElementById('ajouterLigne').addEventListener('click', () => { const options=optionsComptes.map(c=>`<option value="${c.id}">${c.texte}</option>`).join(''); document.querySelector('#tableLignes tbody').insertAdjacentHTML('beforeend', `<tr><td><select name="lignes[${ligneIndex}][compte_id]" class="form-select form-select-sm compte-search" required><option value="">Choisir compte</option>${options}</select></td><td><input name="lignes[${ligneIndex}][libelle]" class="form-control form-control-sm" required></td><td><input type="number" name="lignes[${ligneIndex}][montant]" class="form-control form-control-sm montant" min="0.01" step="0.01" value="0" required></td><td class="text-center"><button type="button" class="btn btn-danger btn-sm supprimer"><i class="bi bi-trash"></i></button></td></tr>`); activerRecherche('#tableLignes tbody tr:last-child .compte-search','Rechercher un compte'); ligneIndex++; });
document.addEventListener('click', e => { const bouton=e.target.closest('.supprimer'); if(bouton && document.querySelectorAll('#tableLignes tbody tr').length > 1){ bouton.closest('tr').remove(); calculerTotal(); } });
document.addEventListener('input', e => { if(e.target.classList.contains('montant')) calculerTotal(); }); document.getElementById('monnaie').addEventListener('change', calculerTotal);
</script>
@endsection
