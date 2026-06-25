@extends('layouts.app')

@section('content')

<div class="container py-4">

    <h3 class="mb-4">📊 JOURNAL  BANQUE MULTI-MONNAIES</h3>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label>Date début</label>
                        <input type="date" name="date_debut" class="form-control"
                            value="{{ request('date_debut') }}">
                    </div>

                    <div class="col-md-4">
                        <label>Date fin</label>
                        <input type="date" name="date_fin" class="form-control"
                            value="{{ request('date_fin') }}">
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary w-100">
                            🔍 Filtrer
                        </button>
                    </div>
                </form>
            <table class="table table-bordered table-hover text-center align-middle">

                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Entrées CDF</th>
                        <th>Sorties CDF</th>
                        <th>Entrées USD</th>
                        <th>Sorties USD</th>
                        <th>Total Entrée CDF</th>
                        <th>Total Sortie CDF</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($journaux as $j)
                        <tr>
                            <td>{{ $j->date }}</td>
                            <td>{{ $j->reference }}</td>
                            <td>{{ number_format($j->entrees_cdf, 2) }}</td>
                            <td>{{ number_format($j->sorties_cdf, 2) }}</td>
                            <td>{{ number_format($j->entrees_usd, 2) }}</td>
                            <td>{{ number_format($j->sorties_usd, 2) }}</td>

                            <td>
                                {{ $j->monnaie === 'USD'
                                    ? number_format($j->entrees_usd * ($taux->taux_de_change ?? 0), 2)
                                    : number_format($j->entrees_cdf, 2)
                                }}
                            </td>

                            <td>
                                {{ $j->monnaie === 'USD'
                                    ? number_format($j->sorties_usd * ($taux->taux_de_change ?? 0), 2)
                                    : number_format($j->sorties_cdf, 2)
                                }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <!-- 🔥 TOTAL -->
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td colspan="2">TOTAUX</td>

                        <td>{{ number_format($totaux['entrees_cdf'], 2) }}</td>
                        <td>{{ number_format($totaux['sorties_cdf'], 2) }}</td>
                        <td>{{ number_format($totaux['entrees_usd'], 2) }}</td>
                        <td>{{ number_format($totaux['sorties_usd'], 2) }}</td>
                        <td>{{ number_format($totaux['total_entrees_cdf'], 2) }}</td>
                        <td>{{ number_format($totaux['total_sorties_cdf'], 2) }}</td>
                    </tr>
                </tfoot>

            </table>

        </div>
    </div>

</div>

<!-- 🔥 CADRE SOLDE -->
<div class="row mb-4">

    <div class="col-md-4">
        <div class="card shadow-sm border-primary">
            <div class="card-body text-center">
                <h6>💰 Solde CDF</h6>
                <h4 class="text-primary">
                    {{ number_format($soldes['cdf'], 2) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-success">
            <div class="card-body text-center">
                <h6>💵 Solde USD</h6>
                <h4 class="text-success">
                    {{ number_format($soldes['usd'], 2) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-dark">
            <div class="card-body text-center">
                <h6>🔄 Solde Converti CDF</h6>
                <h4 class="text-dark">
                    {{ number_format($soldes['usd_cdf'], 2) }}
                </h4>
            </div>
        </div>
    </div>
        <div class="mt-3">
            {{ $journaux->links() }}
        </div>
</div>

@endsection

<script>
    setInterval(() => {
        location.reload();
    }, 120000); // 120 000 ms = 2 minutes
</script>