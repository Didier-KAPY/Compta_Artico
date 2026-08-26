@extends('layouts.app')
@section('content')
<div class="container-fluid financial-report">
    <div class="d-flex justify-content-between mb-3 no-print">
        <a href="{{ route('comptabilite.etats-financiers.bilan', ['date_debut' => $dateDebut, 'date_fin' => $dateFin]) }}" class="btn btn-outline-secondary">Retour au bilan</a>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-secondary">Imprimer</button>
            <form method="POST" action="{{ route('comptabilite.etats-financiers.bilan-initial.supprimer', $bilanInitial) }}" onsubmit="return confirm('Voulez-vous vraiment supprimer ce bilan initial archivé ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </form>
        </div>
    </div>

    <header class="text-center mb-3">
        <h4>{{ $entreprise?->nom_entreprise ?? 'Entreprise' }}</h4>
        @if($entreprise?->adresse)<div>{{ $entreprise->adresse }}</div>@endif
        <h2>Bilan initial</h2>
        <div class="mb-1"><strong>{{ $bilanInitial->libelle }}</strong></div>
        <div>Du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }} — Devise de présentation : Franc congolais (CDF)</div>
        <div class="text-muted">Archivé le {{ $bilanInitial->created_at->format('d/m/Y') }}</div>
    </header>

    <div class="row g-3">
        @foreach(['actif' => 'ACTIF', 'passif' => 'PASSIF'] as $sens => $titre)
            <div class="col-12">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr><th colspan="3">{{ $titre }}</th></tr>
                        <tr><th>Réf.</th><th>Libellé</th><th>Exercice N</th></tr>
                    </thead>
                    <tbody>
                        @foreach($etats['bilan'][$sens] as $section)
                            <tr class="table-secondary fw-bold">
                                <td colspan="2">{{ $section['label'] }}</td>
                                <td class="text-end">{{ number_format($section['total_actuel'], 2, ',', ' ') }}</td>
                            </tr>
                            @foreach($section['lignes'] as $ligne)
                                <tr>
                                    <td>{{ $ligne['code'] }}</td>
                                    <td>{{ $ligne['label'] }}</td>
                                    <td class="text-end">{{ number_format(abs((float) $ligne['actuel']), 2, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark fw-bold">
                        <tr><td colspan="2">TOTAL {{ $titre }}</td><td class="text-end">{{ number_format($etats['bilan']['total_'.$sens], 2, ',', ' ') }}</td></tr>
                    </tfoot>
                </table>
            </div>
        @endforeach
    </div>

    <div class="alert alert-{{ $etats['bilan']['equilibre'] ? 'success' : 'danger' }} py-2">
        <strong>Total actif : {{ number_format($etats['bilan']['total_actif'], 2, ',', ' ') }} — Total passif : {{ number_format($etats['bilan']['total_passif'], 2, ',', ' ') }} — Écart : {{ number_format($etats['bilan']['ecart'], 2, ',', ' ') }}</strong>
        <span class="badge bg-{{ $etats['bilan']['equilibre'] ? 'success' : 'danger' }}">{{ $etats['bilan']['equilibre'] ? 'Équilibré' : 'Non équilibré' }}</span>
    </div>
</div>
<style>
.financial-report td,.financial-report th{padding:.25rem .4rem}
@media print{@page{size:A4 portrait;margin:8mm}.sidebar,.navbar,.no-print{display:none!important}.content{margin:0!important;padding:0!important}.financial-report{font-size:10px}}
</style>
@endsection