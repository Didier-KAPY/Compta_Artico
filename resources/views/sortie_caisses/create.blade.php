@extends('layouts.app')
@section('content')
<div class="container py-4"><div class="card shadow-sm border-0">
@if(session('success'))<div class="alert alert-success mb-0">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger mb-0">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger mb-0"><strong>Veuillez corriger les informations :</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card-header bg-dark text-white d-flex align-items-center"><i class="bi bi-box-arrow-up me-2"></i><h5 class="mb-0">Nouveau Bon de Sortie</h5></div>
<form action="{{ route('sortie-caisses.store') }}" method="POST">@csrf
<div class="card-body"><input type="hidden" id="taux_tva" value="{{ $tauxTva }}">
<div class="row">
<div class="col-md-4 mb-3"><label class="form-label">Date</label><input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" class="form-control" required></div>
<div class="col-md-4 mb-3"><label class="form-label">Type de bon</label><select name="type_bon" class="form-select" required>
<option value="BSC" {{ old('type_bon', 'BSC') === 'BSC' ? 'selected' : '' }}>BSC — Bon de sortie caisse</option>
<option value="BSM" {{ old('type_bon') === 'BSM' ? 'selected' : '' }}>BSM — Bon de sortie Mobile Money</option>
<option value="BSB" {{ old('type_bon') === 'BSB' ? 'selected' : '' }}>BSB — Bon de sortie bancaire</option>
</select></div>
<div class="col-md-4 mb-3"><label class="form-label">Monnaie</label><select name="monnaie" class="form-select" required><option value="CDF" {{ old('monnaie', 'CDF') === 'CDF' ? 'selected' : '' }}>CDF</option><option value="USD" {{ old('monnaie') === 'USD' ? 'selected' : '' }}>USD</option></select></div>
<div class="col-md-8 mb-3"><label class="form-label">Bénéficiaire</label><input type="text" name="beneficiaire" value="{{ old('beneficiaire') }}" class="form-control" required></div>
@php $appliquerTva = (string) old('appliquer_tva', '0'); @endphp
<div class="col-md-4 mb-3"><label class="form-label d-block">Traitement TVA</label><div class="btn-group">
<input type="radio" class="btn-check" name="appliquer_tva" id="sans_tva" value="0" {{ $appliquerTva === '0' ? 'checked' : '' }}><label class="btn btn-outline-secondary" for="sans_tva">Sans TVA</label>
<input type="radio" class="btn-check" name="appliquer_tva" id="avec_tva" value="1" {{ $appliquerTva === '1' ? 'checked' : '' }}><label class="btn btn-outline-primary" for="avec_tva">Avec TVA</label>
</div></div>
<div class="col-12 mb-3"><label class="form-label">Observation</label><textarea name="observation" rows="2" class="form-control">{{ old('observation') }}</textarea></div>
</div><hr>
<div class="d-flex justify-content-between align-items-center mb-2"><h5>Lignes du bon de sortie</h5><button type="button" class="btn btn-success" id="add-row">+ Ajouter ligne</button></div>
<div class="table-responsive"><table class="table table-bordered" id="table-lignes"><thead class="table-dark"><tr><th>Désignation</th><th>Quantité</th><th>Prix unitaire</th><th>Montant</th><th>Action</th></tr></thead><tbody>
<tr><td><input type="text" name="designation[]" class="form-control" required></td><td><input type="text" inputmode="decimal" name="quantite[]" class="form-control qty" value="1" required></td><td><input type="text" inputmode="decimal" name="prix_unitaire[]" class="form-control price" value="0" required></td><td><input type="text" class="form-control total" value="0.00" readonly></td><td><button type="button" class="btn btn-danger remove-row">X</button></td></tr>
</tbody></table></div>
<div class="text-end mt-3"><h5>Total général : <span id="total-general">0.00</span></h5><div id="tva-summary" class="small text-muted">Montant HT : <strong id="total-ht">0.00</strong> · TVA : <strong id="total-tva">0.00</strong></div></div>
</div>
<div class="card-footer bg-white d-flex justify-content-between"><a href="{{ route('sortie-caisses.index') }}" class="btn btn-outline-secondary">Annuler</a><button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Enregistrer</button></div>
</form></div></div>
@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.querySelector('#table-lignes tbody');
    const tauxTva = parseFloat(document.getElementById('taux_tva').value) || 0;
    function calculer() {
        let total = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            const quantite = parseFloat((row.querySelector('.qty').value || '').replace(',', '.')) || 0;
            const prix = parseFloat((row.querySelector('.price').value || '').replace(',', '.')) || 0;
            const montant = quantite * prix;
            row.querySelector('.total').value = montant.toFixed(2); total += montant;
        });
        const avecTva = document.querySelector('input[name="appliquer_tva"]:checked')?.value === '1';
        const ht = avecTva && tauxTva > 0 ? total / (1 + tauxTva / 100) : total;
        document.getElementById('total-general').textContent = total.toFixed(2);
        document.getElementById('total-ht').textContent = ht.toFixed(2);
        document.getElementById('total-tva').textContent = (total - ht).toFixed(2);
        document.getElementById('tva-summary').style.display = avecTva ? '' : 'none';
    }
    document.getElementById('add-row').addEventListener('click', function () {
        const row = document.createElement('tr');
        row.innerHTML = '<td><input type="text" name="designation[]" class="form-control" required></td><td><input type="text" inputmode="decimal" name="quantite[]" class="form-control qty" value="1" required></td><td><input type="text" inputmode="decimal" name="prix_unitaire[]" class="form-control price" value="0" required></td><td><input type="text" class="form-control total" value="0.00" readonly></td><td><button type="button" class="btn btn-danger remove-row">X</button></td>';
        tbody.appendChild(row); calculer();
    });
    tbody.addEventListener('input', calculer);
    document.addEventListener('click', function (event) { if (event.target.classList.contains('remove-row') && tbody.querySelectorAll('tr').length > 1) { event.target.closest('tr').remove(); calculer(); } });
    document.querySelectorAll('input[name="appliquer_tva"]').forEach(function (input) { input.addEventListener('change', calculer); });
    calculer();
});
</script>
@endsection