@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="card shadow-sm border-0">
        @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Veuillez corriger les informations suivantes :</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        <!-- HEADER -->
        <div class="card-header bg-dark text-white d-flex align-items-center">
            <i class="bi bi-cash-coin me-2"></i>
            <h5 class="mb-0">Nouvelle Bon d'Entrée</h5>
        </div>

        <div class="card-body">

            <form action="{{ route('entree-caisses.store') }}" method="POST">
                @csrf

                <!-- 🔥 TOTAL CACHÉ ENVOYÉ EN BASE -->
                <input type="hidden" name="montant_total" id="montant_total">

                <!-- INFO PRINCIPALE -->
                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" class="form-control @error('date') is-invalid @enderror" required>
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Type de bon</label>
                        <select name="type_bon" class="form-select @error('type_bon') is-invalid @enderror" required>
                            <option value="BEC" {{ old('type_bon', 'BEC') === 'BEC' ? 'selected' : '' }}>BEC — Bon d’entrée caisse</option>
                            <option value="BEM" {{ old('type_bon') === 'BEM' ? 'selected' : '' }}>BEM — Bon d’entrée Mobile Money</option>
                            <option value="BEB" {{ old('type_bon') === 'BEB' ? 'selected' : '' }}>BEB — Bon d’entrée bancaire</option>
                        </select>
                        @error('type_bon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Ce code sera utilisé comme préfixe du numéro du bon.</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Monnaie</label>
                        <select name="monnaie" class="form-select" required>
                            <option value="CDF" {{ old('monnaie', 'CDF') === 'CDF' ? 'selected' : '' }}>CDF</option>
                            <option value="USD" {{ old('monnaie') === 'USD' ? 'selected' : '' }}>USD</option>
                        </select>
                    </div>

                    @php $appliquerTva = (string) old('appliquer_tva', '0'); @endphp
                    <div class="col-md-8 mb-3">
                        <label class="form-label d-block">Traitement TVA</label>
                        <div class="btn-group" role="group" aria-label="Traitement TVA">
                            <input type="radio" class="btn-check" name="appliquer_tva" id="sans_tva" value="0" {{ $appliquerTva === '0' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary" for="sans_tva"><i class="bi bi-slash-circle me-1"></i>Sans TVA</label>
                            <input type="radio" class="btn-check" name="appliquer_tva" id="avec_tva" value="1" {{ $appliquerTva === '1' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary" for="avec_tva"><i class="bi bi-percent me-1"></i>Avec TVA</label>
                        </div>
                    </div>
                    <input type="hidden" id="taux_tva" value="{{ $tauxTva }}">

                    <!-- <div class="col-md-4 mb-3">
                        <label>Type de transaction</label>
                        <select name="type" class="form-select" required>
                            <option value="Caisse">Caisse</option>
                            <option value="Banque">Banque</option>
                            <option value="Monnaie électronique">Monnaie électronique</option>
                        </select>
                    </div>-->


                </div>

                <hr>

                <!-- LIGNES -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5>Lignes d’entrée de caisse</h5>

                    <button type="button" class="btn btn-success" id="add-row">
                        + Ajouter ligne
                    </button>
                </div>

                <table class="table table-bordered" id="table-lignes">

                    <thead class="table-dark">
                        <tr>
                            <th>Désignation</th>
                            <th>Quantité</th>
                            <th>Prix Unitaire</th>
                            <th>Montant</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>
                                <input type="text" name="designation[]" class="form-control" required>
                            </td>

                            <td>
                                <input type="text" inputmode="decimal" name="quantite[]" class="form-control qty" value="1">
                            </td>

                            <td>
                                <input type="text" inputmode="decimal" name="prix_unitaire[]" class="form-control price" value="0">
                            </td>

                            <td>
                                <input type="number" class="form-control total" readonly value="0">
                            </td>

                            <td>
                                <button type="button" class="btn btn-danger remove-row">X</button>
                            </td>
                        </tr>
                    </tbody>

                </table>

                <!-- TOTAL GENERAL -->
                <div class="text-end mt-3">
                    <h5>
                        Total général :
                        <span id="total-general">0</span>
                    </h5>
                    <div id="tva-summary" class="small text-muted">
                        Montant HT : <strong id="total-ht">0.00</strong> · TVA : <strong id="total-tva">0.00</strong>
                    </div>
                </div>

                <!-- BUTTON -->
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        Enregistrer
                    </button>
                </div>

            </form>

        </div>
    </div>
    
</div>

@endsection


@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const tbody = document.querySelector('#table-lignes tbody');
    const totalGeneral = document.getElementById('total-general');
    const btnAdd = document.getElementById('add-row');
    const inputTotal = document.getElementById('montant_total');
    const tvaRate = document.getElementById('taux_tva');
    const tvaSummary = document.getElementById('tva-summary');
    const totalHt = document.getElementById('total-ht');
    const totalTva = document.getElementById('total-tva');

    // AJOUT LIGNE
    btnAdd.addEventListener('click', function () {

        const row = document.createElement('tr');

        row.innerHTML = `
            <td><input type="text" name="designation[]" class="form-control" required></td>
            <td><input type="text" inputmode="decimal" name="quantite[]" class="form-control qty" value="1"></td>
            <td><input type="text" inputmode="decimal" name="prix_unitaire[]" class="form-control price" value="0"></td>
            <td><input type="number" class="form-control total" readonly value="0"></td>
            <td><button type="button" class="btn btn-danger remove-row">X</button></td>
        `;

        tbody.appendChild(row);
        calculTotal();
    });

    // SUPPRESSION LIGNE
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
            calculTotal();
        }
    });

    // CALCUL AUTOMATIQUE
    tbody.addEventListener('input', function () {
        calculTotal();
    });

    function calculTotal() {

        let total = 0;

        tbody.querySelectorAll('tr').forEach(row => {

            const qty = parseFloat((row.querySelector('.qty')?.value || '').replace(',', '.')) || 0;
            const price = parseFloat((row.querySelector('.price')?.value || '').replace(',', '.')) || 0;

            const lineTotal = qty * price;

            row.querySelector('.total').value = lineTotal.toFixed(2);

            total += lineTotal;
        });

        totalGeneral.innerText = total.toFixed(2);

        // 🔥 ENVOI AU BACKEND
        inputTotal.value = total.toFixed(2);
        calculTva(total);
    }

    function calculTva(total) {
        const avecTva = document.querySelector('input[name="appliquer_tva"]:checked')?.value === '1';
        const taux = avecTva ? (parseFloat(tvaRate.value.replace(',', '.')) || 0) : 0;
        const ht = avecTva && taux > 0 ? total / (1 + taux / 100) : total;
        tvaSummary.style.display = avecTva ? '' : 'none';
        totalHt.textContent = ht.toFixed(2);
        totalTva.textContent = (total - ht).toFixed(2);
    }

    document.querySelectorAll('input[name="appliquer_tva"]').forEach(input => input.addEventListener('change', calculTotal));
    calculTotal();

});
</script>
@endsection