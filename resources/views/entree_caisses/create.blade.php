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
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Monnaie</label>
                        <select name="monnaie" class="form-select" required>
                            <option value="CDF">CDF</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>

                    <!-- <div class="col-md-4 mb-3">
                        <label>Type de transaction</label>
                        <select name="type" class="form-select" required>
                            <option value="Caisse">Caisse</option>
                            <option value="Banque">Banque</option>
                            <option value="Monnaie électronique">Monnaie électronique</option>
                        </select>
                    </div>-->

                    <div class="col-md-12 mb-3">
                        <label>Motif</label>
                        <input type="text" name="motif" class="form-control" required>
                    </div>

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
                                <input type="number" name="quantite[]" class="form-control qty" value="1">
                            </td>

                            <td>
                                <input type="number" name="prix_unitaire[]" class="form-control price" value="0">
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
                </div>

                <!-- BUTTON -->
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-primary">
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

    // AJOUT LIGNE
    btnAdd.addEventListener('click', function () {

        const row = document.createElement('tr');

        row.innerHTML = `
            <td><input type="text" name="designation[]" class="form-control" required></td>
            <td><input type="number" name="quantite[]" class="form-control qty" value="1"></td>
            <td><input type="number" name="prix_unitaire[]" class="form-control price" value="0"></td>
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

            const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
            const price = parseFloat(row.querySelector('.price')?.value) || 0;

            const lineTotal = qty * price;

            row.querySelector('.total').value = lineTotal.toFixed(2);

            total += lineTotal;
        });

        totalGeneral.innerText = total.toFixed(2);

        // 🔥 ENVOI AU BACKEND
        inputTotal.value = total.toFixed(2);
    }

});
</script>
@endsection