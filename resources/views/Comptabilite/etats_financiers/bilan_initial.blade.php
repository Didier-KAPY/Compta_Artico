@extends('layouts.app')
@section('content')
<div class="container-fluid financial-report">
    <div class="d-flex justify-content-between mb-3 no-print">
        <a href="{{ route('comptabilite.etats-financiers.bilan', ['date_debut' => $dateDebut, 'date_fin' => $dateFin]) }}" class="btn btn-outline-secondary">Retour au bilan</a>
        <div class="d-flex gap-2">
            <a href="{{ route('comptabilite.etats-financiers.bilan-initial.pdf', $bilanInitial) }}" class="btn btn-danger">Télécharger PDF</a>
            <button type="button" onclick="window.print()" class="btn btn-secondary">Imprimer</button>
            <form method="POST" action="{{ route('comptabilite.etats-financiers.bilan-initial.supprimer', $bilanInitial) }}" onsubmit="return confirm('Voulez-vous vraiment supprimer ce bilan initial archivé ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </form>
        </div>
    </div>

    <header class="archive-header text-center mb-3">
        @if($entreprise?->logo)
            <img class="archive-logo" src="{{ asset('storage/'.$entreprise->logo) }}" alt="Logo {{ $entreprise->nom_entreprise ?? 'Artico' }}">
        @endif
        <div class="archive-company">{{ $entreprise?->nom_entreprise ?? 'COMPTA ARTICO' }}</div>
        @if($entreprise?->slogan)<div class="archive-slogan">{{ $entreprise->slogan }}</div>@endif
        @if($entreprise?->adresse || $entreprise?->telephone)
            <div class="archive-contact">{{ $entreprise?->adresse }} @if($entreprise?->telephone) — Tél. {{ $entreprise->telephone }} @endif</div>
        @endif
        <h2>Bilan d’ouverture</h2>
        <div class="mb-1"><strong>{{ $bilanInitial->libelle }}</strong></div>
        <div>Devise de présentation : Franc congolais (CDF)</div>
        <div>Date : 01/08/2026</div>
        <div class="text-muted">Date d’archivage : {{ $bilanInitial->created_at->format('d/m/Y') }}</div>
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

    <div class="archive-approval">
        <div class="approval-item">
            <strong>Signature du chargé des finances</strong>
            @if($chargeFinances?->signature)
                <img src="{{ asset('storage/'.$chargeFinances->signature) }}" alt="Signature du chargé des finances">
            @endif
            @if($chargeFinances)<div>{{ trim($chargeFinances->prenom.' '.$chargeFinances->nom) }}</div>@endif
        </div>
        <div class="approval-item">
            <strong>Cachet de l’entreprise</strong>
            @if($entreprise?->cachet)
                <img src="{{ asset('storage/'.$entreprise->cachet) }}" alt="Cachet de l’entreprise">
            @endif
        </div>
        <div class="approval-item">
            <strong>Signature du gérant</strong>
            @if($gerant?->signature)
                <img src="{{ asset('storage/'.$gerant->signature) }}" alt="Signature du gérant">
            @endif
            @if($gerant)<div>{{ trim($gerant->prenom.' '.$gerant->nom) }}</div>@endif
        </div>
    </div>
</div>
<style>
.financial-report td,.financial-report th{padding:.25rem .4rem}
.archive-header{border-bottom:3px solid #176b4d;padding-bottom:10px}
.archive-logo{display:block;max-width:70px;max-height:70px;margin:0 auto 6px;object-fit:contain}
.archive-company{font-size:20px;font-weight:bold;color:#176b4d;text-transform:uppercase}
.archive-slogan{font-style:italic;font-weight:bold;color:#334155}
.archive-contact{color:#64748b}
.archive-header h2{margin:14px 0 4px;text-transform:uppercase}
.archive-approval{display:flex;justify-content:space-around;gap:25px;margin:35px 10px 10px;text-align:center;break-inside:avoid}
.approval-item{width:190px;min-height:100px}
.approval-item img{display:block;width:180px;height:80px;margin:8px auto 3px;object-fit:contain}
@media print{@page{size:A4 portrait;margin:8mm}.sidebar,.navbar,.no-print{display:none!important}.content{margin:0!important;padding:0!important}.financial-report{font-size:10px}}
</style>
@endsection
