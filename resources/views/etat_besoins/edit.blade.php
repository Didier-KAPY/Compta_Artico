@extends('layouts.app')

@section('content')

<div class="container py-4">

    <h4>Modifier État de Besoin</h4>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form action="{{ route('etat-besoins.update', $etat->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Département</label>
            <select name="departement_id" class="form-select" required>
                @foreach($departements as $departement)
                    <option value="{{ $departement->id }}" @selected((string)old('departement_id', $etat->departement_id)===(string)$departement->id)>{{ $departement->designation }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Demandeur</label>
            <input type="text" name="demandeur" class="form-control" value="{{ old('demandeur', $etat->demandeur) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Monnaie</label>
            <select name="monnaie" id="monnaie" class="form-select" required>
                <option value="CDF" @selected(old('monnaie', $etat->monnaie)==='CDF')>CDF</option>
                <option value="USD" @selected(old('monnaie', $etat->monnaie)==='USD')>USD</option>
            </select>
        </div>

        @if(config('features.budget'))<div class="mb-3">
            <label class="form-label">Rubrique budgétaire</label>
            <select name="ligne_budgetaire_id" class="form-select">
                <option value="">-- Aucune rubrique --</option>
                @foreach($lignesBudgetaires as $ligne)
                    <option value="{{ $ligne->id }}" @selected((string)old('ligne_budgetaire_id', $etat->ligne_budgetaire_id)===(string)$ligne->id)>
                        {{ $ligne->rubriqueBudgetaire?->designation ?? $ligne->rubrique }} — {{ $ligne->compte?->compte }} {{ $ligne->compte?->designation }}
                    </option>
                @endforeach
            </select>
        </div>@else
            <input type="hidden" name="ligne_budgetaire_id" value="{{ $etat->ligne_budgetaire_id }}">
        @endif

        @php
            $lignesFormulaire = old('designation')
                ? collect(old('designation'))->map(fn($designation, $index) => ['designation'=>$designation, 'quantite'=>old('quantite')[$index] ?? 1, 'prix_unitaire'=>old('prix_unitaire')[$index] ?? 0])
                : $etat->lignes->map(fn($ligne) => ['designation'=>$ligne->designation, 'quantite'=>$ligne->quantite, 'prix_unitaire'=>$ligne->prix_unitaire]);
            if ($lignesFormulaire->isEmpty()) $lignesFormulaire = collect([['designation'=>'', 'quantite'=>1, 'prix_unitaire'=>$etat->montant_estime ?: 0]]);
        @endphp

        <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Lignes du besoin</h5><button type="button" class="btn btn-success btn-sm" id="add-row"><i class="bi bi-plus-lg me-1"></i>Ajouter une ligne</button></div>
        <div class="table-responsive"><table class="table table-bordered align-middle" id="table-lignes">
            <thead class="table-dark"><tr><th>Désignation</th><th style="width:130px">Quantité</th><th style="width:170px">Prix unitaire</th><th style="width:170px">Montant</th><th style="width:70px"></th></tr></thead>
            <tbody>
                @foreach($lignesFormulaire as $ligne)
                <tr>
                    <td><input type="text" name="designation[]" class="form-control" value="{{ $ligne['designation'] }}" required></td>
                    <td><input type="number" name="quantite[]" class="form-control qty" min="1" value="{{ $ligne['quantite'] }}" required></td>
                    <td><div class="input-group"><input type="number" name="prix_unitaire[]" class="form-control price" min="0.01" step="0.01" value="{{ $ligne['prix_unitaire'] }}" required><span class="input-group-text currency-label">{{ old('monnaie', $etat->monnaie) }}</span></div></td>
                    <td><input type="text" class="form-control total" readonly></td>
                    <td><button type="button" class="btn btn-outline-danger remove-row" title="Retirer"><i class="bi bi-trash"></i></button></td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        <div class="text-end mb-3"><strong>Total estimé : <span id="total-general">0.00</span> <span class="currency-label">{{ old('monnaie', $etat->monnaie) }}</span></strong></div>

        <button class="btn btn-primary">Modifier</button>

    </form>

</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.querySelector('#table-lignes tbody');
    const totalGeneral = document.getElementById('total-general');
    const monnaie = document.getElementById('monnaie');

    function actualiserMonnaie() {
        document.querySelectorAll('.currency-label').forEach(label => label.textContent = monnaie.value);
    }

    function calculer() {
        let total = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            const quantite = parseFloat(row.querySelector('.qty').value) || 0;
            const prix = parseFloat(row.querySelector('.price').value) || 0;
            const montant = quantite * prix;
            row.querySelector('.total').value = montant.toFixed(2);
            total += montant;
        });
        totalGeneral.textContent = total.toFixed(2);
    }

    document.getElementById('add-row').addEventListener('click', function () {
        const row = document.createElement('tr');
        row.innerHTML = `<td><input type="text" name="designation[]" class="form-control" required></td><td><input type="number" name="quantite[]" class="form-control qty" min="1" value="1" required></td><td><div class="input-group"><input type="number" name="prix_unitaire[]" class="form-control price" min="0.01" step="0.01" required><span class="input-group-text currency-label"></span></div></td><td><input type="text" class="form-control total" readonly></td><td><button type="button" class="btn btn-outline-danger remove-row" title="Retirer"><i class="bi bi-trash"></i></button></td>`;
        tbody.appendChild(row);
        actualiserMonnaie();
        calculer();
    });

    tbody.addEventListener('input', calculer);
    monnaie.addEventListener('change', actualiserMonnaie);
    tbody.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-row');
        if (!button) return;
        if (tbody.querySelectorAll('tr').length === 1) return window.appNotify('Au moins une ligne est obligatoire.');
        button.closest('tr').remove();
        calculer();
    });
    calculer();
    actualiserMonnaie();
});
</script>
@endsection
