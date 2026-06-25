@extends('layouts.app')

@section('content')

<div class="container py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">📊 Dashboard Entrées de Caisse</h4>
    </div>

    <!-- FILTRE AUTO -->
    <form method="GET" id="filterForm" class="mb-4 d-flex gap-2">

        <!-- ANNÉE -->
        <select name="year" class="form-select w-auto" onchange="document.getElementById('filterForm').submit();">
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endforeach
        </select>

        <!-- MOIS -->
        <select name="month" class="form-select w-auto" onchange="document.getElementById('filterForm').submit();">
            <option value="">Tous les mois</option>

            @foreach(range(1,12) as $m)
                <option value="{{ $m }}" {{ (int)$month === $m ? 'selected' : '' }}>
                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                </option>
            @endforeach
        </select>

    </form>

    <!-- KPI -->
    @php
        $kpis = [
            ['label'=>'Total','value'=>$totalEntrees,'class'=>'primary'],
            ['label'=>'En attente','value'=>$enAttente,'class'=>'warning'],
            ['label'=>'Validés','value'=>$totalValidees,'class'=>'success'],
            ['label'=>'Rejetés','value'=>$totalRejetees,'class'=>'danger'],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach($kpis as $kpi)
            <div class="col-6 col-md-3">
                <div class="card kpi-card shadow-sm text-center border-0">
                    <div class="card-body">
                        <div class="text-muted">{{ $kpi['label'] }}</div>
                        <h2 class="fw-bold text-{{ $kpi['class'] }}">
                            {{ $kpi['value'] }}
                        </h2>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- GRAPHIQUES -->
    <div class="row g-4">

        <div class="col-md-6">
            <div class="card shadow-sm p-3 h-100">
                <h6>📊 Répartition</h6>
                <canvas id="statChart"></canvas>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm p-3 h-100">
                <h6>📈 Évolution</h6>
                <canvas id="lineChart"></canvas>
            </div>
        </div>

    </div>

</div>

@endsection


@section('styles')
<style>
.kpi-card{
    transition:0.3s;
    border-radius:12px;
}
.kpi-card:hover{
    transform:scale(1.03);
    box-shadow:0 10px 25px rgba(0,0,0,0.15);
}
</style>
@endsection


@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// 📊 DONUT
const statChart = new Chart(document.getElementById('statChart'), {
    type: 'doughnut',
    data: {
        labels: ['Validés','En attente','Rejetés'],
        datasets: [{
            data: [
                {{ $totalValidees }},
                {{ $enAttente }},
                {{ $totalRejetees }}
            ],
            backgroundColor: ['#28a745','#ffc107','#dc3545']
        }]
    }
});

// 📈 LINE
const lineChart = new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($labels) !!},
        datasets: [{
            label: 'Entrées',
            data: {!! json_encode($values) !!},
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.2)',
            fill: true,
            tension: 0.4
        }]
    }
});
</script>
@endsection