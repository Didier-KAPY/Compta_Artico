@extends('layouts.app')
@section('content')
<div class="container-fluid financial-report">
    <div class="d-flex justify-content-end mb-2 no-print">@include('partials.period-export-buttons', ['rapport'=>'compte-resultat','exportParams'=>['date_debut'=>$dateDebut,'date_fin'=>$dateFin],'showPdf'=>false])</div>
    <div class="d-flex justify-content-between mb-2 no-print"><a href="{{ route('comptabilite.etats-financiers.index', request()->query()) }}" class="btn btn-outline-secondary">Retour</a><div><button onclick="window.print()" class="btn btn-secondary">Imprimer</button> <a href="{{ route('comptabilite.etats-financiers.compte-resultat-pdf', request()->query()) }}" class="btn btn-danger">PDF</a></div></div>
    <header class="text-center mb-3"><h4>{{ $entreprise?->nom_entreprise ?? 'Entreprise' }}</h4>@if($entreprise?->adresse)<div>{{ $entreprise->adresse }}</div>@endif<h2>Compte de résultat</h2><div>Du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }} — Devise de présentation : Franc congolais (CDF)</div></header>
    <form method="GET" class="row g-2 mb-3 no-print"><div class="col-4"><input type="date" name="date_debut" value="{{ $dateDebut }}" class="form-control"></div><div class="col-4"><input type="date" name="date_fin" value="{{ $dateFin }}" class="form-control"></div><div class="col-2"><button class="btn btn-primary">Actualiser</button></div></form>
    <table class="table table-bordered table-sm">
        <thead class="table-dark"><tr><th>Réf.</th><th>Libellé</th><th class="text-end">Exercice N</th></tr></thead><tbody>
        @foreach(['produits_exploitation','charges_exploitation','produits_financiers','charges_financieres','produits_hao','charges_hao','impot_resultat'] as $section)
            <tr class="table-secondary fw-bold"><td></td><td>{{ $etats['compte_resultat'][$section]['label'] }}</td><td class="text-end">{{ number_format($etats['compte_resultat'][$section]['total_actuel'],2,',',' ') }}</td></tr>
            @foreach($etats['compte_resultat'][$section]['lignes'] as $ligne)<tr><td>{{ $ligne['code'] }}</td><td>{{ $ligne['label'] }}</td><td class="text-end">{{ number_format($ligne['actuel'],2,',',' ') }}</td></tr>@endforeach
            @if($section==='charges_exploitation')<tr class="fw-bold"><td></td><td>Résultat d’exploitation</td><td class="text-end">{{ number_format($etats['compte_resultat']['resultat_exploitation']['actuel'],2,',',' ') }}</td></tr>@endif
            @if($section==='charges_financieres')<tr class="fw-bold"><td></td><td>Résultat financier</td><td class="text-end">{{ number_format($etats['compte_resultat']['resultat_financier']['actuel'],2,',',' ') }}</td></tr><tr class="fw-bold"><td></td><td>Résultat des activités ordinaires</td><td class="text-end">{{ number_format($etats['compte_resultat']['resultat_activites_ordinaires']['actuel'],2,',',' ') }}</td></tr>@endif
            @if($section==='charges_hao')<tr class="fw-bold"><td></td><td>Résultat hors activités ordinaires</td><td class="text-end">{{ number_format($etats['compte_resultat']['resultat_hao']['actuel'],2,',',' ') }}</td></tr>@endif
        @endforeach
        </tbody><tfoot class="table-dark fw-bold"><tr><td></td><td>RÉSULTAT NET — {{ $etats['compte_resultat']['resultat_net']['actuel'] > 0 ? 'Bénéfice' : ($etats['compte_resultat']['resultat_net']['actuel'] < 0 ? 'Perte' : 'Nul') }}</td><td class="text-end">{{ number_format($etats['compte_resultat']['resultat_net']['actuel'],2,',',' ') }}</td></tr></tfoot>
    </table>
    <div class="alert alert-light border py-2">Débit : <b>{{ number_format($etats['controles']['total_debit'],2,',',' ') }}</b> — Crédit : <b>{{ number_format($etats['controles']['total_credit'],2,',',' ') }}</b> — Écart D/C : <b>{{ number_format($etats['controles']['ecart_debit_credit'],2,',',' ') }}</b></div>
    @foreach(['anomalies'=>'Anomalies du plan comptable','non_classes'=>'Comptes non classés'] as $cle=>$titre)
        @if($etats[$cle])<div class="alert alert-warning"><b>{{ $titre }}</b><div class="table-responsive"><table class="table table-sm mb-0"><tr><th>Compte</th><th>Désignation</th><th>Nature</th><th>Observation</th><th>Débit CDF</th><th>Crédit CDF</th><th>Solde CDF</th><th>Raison</th></tr>@foreach($etats[$cle] as $c)<tr><td>{{ $c['compte'] }}</td><td>{{ $c['designation'] }}</td><td>{{ $c['nature'] }}</td><td>{{ $c['observation'] }}</td><td>{{ number_format($c['debit'],2,',',' ') }}</td><td>{{ number_format($c['credit'],2,',',' ') }}</td><td>{{ number_format($c['solde'],2,',',' ') }}</td><td>{{ $c['raison'] }}</td></tr>@endforeach</table></div></div>@endif
    @endforeach
</div>
<style>.financial-report td,.financial-report th{padding:.25rem .4rem}@media print{@page{size:A4 landscape;margin:8mm}.sidebar,.navbar,.no-print{display:none!important}.content{margin:0!important;padding:0!important}.financial-report{font-size:10px}}</style>
@endsection
