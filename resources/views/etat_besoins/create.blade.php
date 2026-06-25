@extends('layouts.app')

@section('content')

<div class="container py-4">
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
    <div class="card shadow-sm border-0">

        <!-- BARRE NOIRE -->
        <div class="card-header bg-dark text-white d-flex align-items-center">
            <i class="bi bi-file-earmark-text me-2"></i>
            <h5 class="mb-0">Nouvel État de Besoin</h5>
        </div>

        <div class="card-body">

            <form action="{{ route('etat-besoins.store') }}" method="POST">
                @csrf

                <!-- INFO PRINCIPALE -->
                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Service</label>
                        <input type="text" name="service" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Monnaie</label>
                        <select name="monnaie" class="form-select" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="CDF">CDF</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>

                </div>

                <div class="mb-3">
                    <label class="form-label">Demandeur</label>
                    <input type="text" name="demandeur" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Motif</label>
                    <textarea name="motif" class="form-control" rows="3" required></textarea>
                </div>

                <hr>

                <!-- LIGNES -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Lignes du besoin</h5>

                    <button type="button" class="btn btn-success" id="add-row">
                        + Ajouter ligne
                    </button>
                </div>

                <table class="table table-bordered" id="table-lignes">

                    <thead class="table-dark">
                        <tr>
                            <th>Désignation</th>
                            <th width="120">Quantité</th>
                            <th width="150">Prix Unitaire</th>
                            <th width="150">Montant</th>
                            <th width="100">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>

                            <td>
                                <input type="text"
                                       name="designation[]"
                                       class="form-control"
                                       required>
                            </td>

                            <td>
                                <input type="number"
                                       name="quantite[]"
                                       class="form-control qty"
                                       value="1"
                                       min="1"
                                       required>
                            </td>

                            <td>
                                <input type="number"
                                       name="prix_unitaire[]"
                                       class="form-control price"
                                       value="0"
                                       min="0.01"
                                       step="0.01"
                                       required>
                            </td>

                            <td>
                                <input type="number"
                                       class="form-control total"
                                       readonly
                                       value="0">
                            </td>

                            <td>
                                <button type="button"
                                        class="btn btn-danger remove-row">
                                    X
                                </button>
                            </td>

                        </tr>
                    </tbody>

                </table>

                <!-- TOTAL GENERAL -->
                <div class="text-end mt-3">
                    <h5>
                        Total général :
                        <span id="total-general">0.00</span>
                    </h5>
                </div>

                <!-- BOUTON -->
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

    // AJOUT D'UNE LIGNE
    btnAdd.addEventListener('click', function () {

        const row = document.createElement('tr');

        row.innerHTML = `
            <td>
                <input type="text"
                       name="designation[]"
                       class="form-control"
                       required>
            </td>

            <td>
                <input type="number"
                       name="quantite[]"
                       class="form-control qty"
                       value="1"
                       min="1"
                       required>
            </td>

            <td>
                <input type="number"
                       name="prix_unitaire[]"
                       class="form-control price"
                       value="0"
                       min="0.01"
                       step="0.01"
                       required>
            </td>

            <td>
                <input type="number"
                       class="form-control total"
                       readonly
                       value="0">
            </td>

            <td>
                <button type="button"
                        class="btn btn-danger remove-row">
                    X
                </button>
            </td>
        `;

        tbody.appendChild(row);
        calculTotal();
    });

    // SUPPRESSION D'UNE LIGNE
    document.addEventListener('click', function (e) {

        if (e.target.classList.contains('remove-row')) {

            if (tbody.querySelectorAll('tr').length > 1) {
                e.target.closest('tr').remove();
                calculTotal();
            } else {
                alert('Au moins une ligne est obligatoire.');
            }

        }

    });

    // CALCUL AUTOMATIQUE
    tbody.addEventListener('input', function (e) {

        if (
            e.target.classList.contains('qty') ||
            e.target.classList.contains('price')
        ) {
            calculTotal();
        }

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
    }

    calculTotal();

});
</script>

@endsection