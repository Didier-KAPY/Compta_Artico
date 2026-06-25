@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <h3 class="mb-4">Tableau de bord comptable</h3>

    <!-- ================= Cartes de synthèse ================= -->
    <div class="row g-3 mb-4">

        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm card-hover">
                <div class="card-body">
                    <h6 class="text-muted">Total comptes</h6>
                    <h4 class="fw-bold">150</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm card-hover">
                <div class="card-body">
                    <h6 class="text-muted">Total écritures</h6>
                    <h4 class="fw-bold">540</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm card-hover">
                <div class="card-body">
                    <h6 class="text-muted">Clients</h6>
                    <h4 class="fw-bold">120</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm card-hover">
                <div class="card-body">
                    <h6 class="text-muted">Fournisseurs</h6>
                    <h4 class="fw-bold">80</h4>
                </div>
            </div>
        </div>

    </div>

    <!-- ================= Graphiques ================= -->
    <div class="row g-3 mb-4">

        <div class="col-lg-6 col-12">
            <div class="card shadow-sm card-hover">
                <div class="card-body">
                    <h6 class="mb-3">Recettes / Dépenses (Mensuel)</h6>
                    <canvas id="revenuesChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="card shadow-sm card-hover">
                <div class="card-body">
                    <h6 class="mb-3">Solde caisse et banque</h6>
                    <canvas id="balanceChart" height="200"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- ================= Dernières écritures ================= -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm card-hover">
                <div class="card-body">
                    <h6 class="mb-3">Dernières écritures</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Compte</th>
                                    <th>Libellé</th>
                                    <th>Débit</th>
                                    <th>Crédit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>01/03/2026</td>
                                    <td>Banque</td>
                                    <td>Vente produit A</td>
                                    <td>0</td>
                                    <td>500</td>
                                </tr>
                                <tr>
                                    <td>02/03/2026</td>
                                    <td>Caisse</td>
                                    <td>Achat fournitures</td>
                                    <td>200</td>
                                    <td>0</td>
                                </tr>
                                <tr>
                                    <td>03/03/2026</td>
                                    <td>Clients</td>
                                    <td>Facture #125</td>
                                    <td>0</td>
                                    <td>300</td>
                                </tr>
                                <tr>
                                    <td>04/03/2026</td>
                                    <td>Fournisseurs</td>
                                    <td>Paiement #78</td>
                                    <td>150</td>
                                    <td>0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctxRevenue = document.getElementById('revenuesChart').getContext('2d');
const revenuesChart = new Chart(ctxRevenue, {
    type: 'bar',
    data: {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
        datasets: [
            { label: 'Recettes', data: [500, 700, 800, 600, 900, 1200], backgroundColor: '#0d6efd' },
            { label: 'Dépenses', data: [400, 600, 700, 500, 600, 900], backgroundColor: '#dc3545' }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } } }
});

const ctxBalance = document.getElementById('balanceChart').getContext('2d');
const balanceChart = new Chart(ctxBalance, {
    type: 'line',
    data: {
        labels: ['Semaine 1', 'Semaine 2', 'Semaine 3', 'Semaine 4'],
        datasets: [{
            label: 'Solde',
            data: [5000, 4800, 5200, 5100],
            borderColor: '#198754',
            backgroundColor: 'rgba(25, 135, 84, 0.2)',
            tension: 0.4,
            fill: true
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>

<!-- CSS personnalisé pour effets hover sur les cartes -->
<style>
.card-hover {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.card-hover:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
}
</style>

@endsection