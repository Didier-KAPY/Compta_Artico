@extends('layouts.app')
@section('content')
<div class="container-fluid financial-report">
    <div class="d-flex justify-content-end mb-2 no-print">@include('partials.period-export-buttons', ['rapport'=>'bilan','exportParams'=>['date_debut'=>$dateDebut,'date_fin'=>$dateFin],'showPdf'=>false])</div>
    <div class="d-flex justify-content-between mb-2 no-print"><a href="{{ route('comptabilite.etats-financiers.index', request()->query()) }}" class="btn btn-outline-secondary">Retour</a><div><button onclick="window.print()" class="btn btn-secondary">Imprimer</button> <a href="{{ route('comptabilite.etats-financiers.bilan-pdf', request()->query()) }}" class="btn btn-danger">PDF</a></div></div>
    <header class="text-center mb-3"><h4>{{ $entreprise?->nom_entreprise ?? 'Entreprise' }}</h4>@if($entreprise?->adresse)<div>{{ $entreprise->adresse }}</div>@endif<h2>Bilan final</h2><div>Du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }} — Devise de présentation : Franc congolais (CDF)</div></header>
    @if(session('success'))<div class="alert alert-success no-print">{{ session('success') }}</div>@endif
    <form method="GET" class="row g-2 mb-3 no-print"><div class="col-4"><input type="date" name="date_debut" value="{{ $dateDebut }}" class="form-control"></div><div class="col-4"><input type="date" name="date_fin" value="{{ $dateFin }}" class="form-control"></div><div class="col-2"><button class="btn btn-primary">Actualiser</button></div></form>
    @if($archiveMode ?? false)
        <div class="alert alert-secondary no-print"><strong>{{ $bilanInitial->libelle }}</strong> — Bilan initial archivé le {{ $bilanInitial->created_at->format('d/m/Y') }}</div>
    @elseif($bilanInitial)
        <div class="mb-3 no-print"><a href="{{ route('comptabilite.etats-financiers.bilan-initial', $bilanInitial) }}" class="btn btn-success">Consulter le bilan initial</a> <span class="text-muted ms-2">Archivé le {{ $bilanInitial->created_at->format('d/m/Y') }}</span></div>
    @else
        <form method="POST" action="{{ route('comptabilite.etats-financiers.bilan-archiver') }}" class="row g-2 mb-3 no-print">
            @csrf
            <input type="hidden" name="date_debut" value="{{ $dateDebut }}">
            <input type="hidden" name="date_fin" value="{{ $dateFin }}">
            <div class="col-md-8">
                <label for="libelle_archive" class="form-label">Libellé de l’archive <span class="text-danger">*</span></label>
                <input id="libelle_archive" type="text" name="libelle" value="{{ old('libelle') }}" class="form-control @error('libelle') is-invalid @enderror" placeholder="Ex. Bilan initial 2026" required>
                @error('libelle')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 d-flex align-items-end"><button type="submit" class="btn btn-success w-100">Archiver le bilan initial</button></div>
        </form>
    @endif
    <div class="row g-3">
    @foreach(['actif'=>'ACTIF','passif'=>'PASSIF'] as $sens=>$titre)
        <div class="col-12"><table class="table table-bordered table-sm"><thead class="table-dark"><tr><th colspan="3">{{ $titre }}</th></tr><tr><th>Réf.</th><th>Libellé</th><th>Exercice N</th></tr></thead><tbody>
        @foreach($etats['bilan'][$sens] as $section)
            <tr class="table-secondary fw-bold"><td colspan="2">{{ $section['label'] }}</td><td class="text-end">{{ number_format($section['total_actuel'],2,',',' ') }}</td></tr>
            @foreach($section['lignes'] as $ligne)<tr><td>{{ $ligne['code'] }}</td><td>{{ $ligne['label'] }}</td><td class="text-end">{{ number_format(abs((float) $ligne['actuel']),2,',',' ') }}</td></tr>@endforeach
        @endforeach
        </tbody><tfoot class="table-dark fw-bold"><tr><td colspan="2">TOTAL {{ $titre }}</td><td class="text-end">{{ number_format($etats['bilan']['total_'.$sens],2,',',' ') }}</td></tr></tfoot></table></div>
    @endforeach
    </div>
    <div class="alert alert-{{ $etats['bilan']['equilibre']?'success':'danger' }} py-2"><strong>Total actif : {{ number_format($etats['bilan']['total_actif'],2,',',' ') }} — Total passif : {{ number_format($etats['bilan']['total_passif'],2,',',' ') }} — Écart : {{ number_format($etats['bilan']['ecart'],2,',',' ') }}</strong> <span class="badge bg-{{ $etats['bilan']['equilibre']?'success':'danger' }}">{{ $etats['bilan']['equilibre']?'Équilibré':'Non équilibré' }}</span></div>
    <div class="alert alert-light border py-2">Débit : <b>{{ number_format($etats['controles']['total_debit'],2,',',' ') }}</b> — Crédit : <b>{{ number_format($etats['controles']['total_credit'],2,',',' ') }}</b> — Écart D/C : <b>{{ number_format($etats['controles']['ecart_debit_credit'],2,',',' ') }}</b> — Résultat net : <b>{{ number_format($etats['controles']['resultat_net'],2,',',' ') }}</b></div>
    @foreach(['anomalies'=>'Anomalies du plan comptable','non_classes'=>'Comptes non classés'] as $cle=>$titre)
        @if($etats[$cle])<div class="alert alert-warning"><b>{{ $titre }}</b><div class="table-responsive"><table class="table table-sm mb-0"><tr><th>Compte</th><th>Désignation</th><th>Nature</th><th>Observation</th><th>Débit CDF</th><th>Crédit CDF</th><th>Solde CDF</th><th>Raison</th></tr>@foreach($etats[$cle] as $c)<tr><td>{{ $c['compte'] }}</td><td>{{ $c['designation'] }}</td><td>{{ $c['nature'] }}</td><td>{{ $c['observation'] }}</td><td>{{ number_format($c['debit'],2,',',' ') }}</td><td>{{ number_format($c['credit'],2,',',' ') }}</td><td>{{ number_format($c['solde'],2,',',' ') }}</td><td>{{ $c['raison'] }}</td></tr>@endforeach</table></div></div>@endif
    @endforeach
</div>
<style>.financial-report td,.financial-report th{padding:.25rem .4rem}@media print{@page{size:A4 portrait;margin:8mm}.sidebar,.navbar,.no-print{display:none!important}.content{margin:0!important;padding:0!important}.financial-report{font-size:10px}}</style>
@endsection
