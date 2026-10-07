<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page{size:A4 landscape;margin:12mm}
        body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#17251f}
        h2,h3,p{text-align:center;margin:4px}
        table{width:100%;border-collapse:collapse;margin-bottom:10px}
        th,td{border:1px solid #cfd8d3;padding:4px;vertical-align:top}
        th{background:#176b4d;color:#fff;text-align:left}
        .right{text-align:right}.section,.total{font-weight:bold;background:#e8efeb}
        .ok{color:#176b4d}.bad{color:#a92323}
        .company-header td{border:0!important;background:#fff!important}
    </style>
</head>
<body>
    @include('exports._company_header', ['logoSrc' => $logoPdfSource ?? null])
    <h2>Bilan</h2>
    <p>
        Exercice N : {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
        &nbsp; | &nbsp;
        Exercice N-1 : {{ \Carbon\Carbon::parse($etats['date_debut_precedente'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($etats['date_fin_precedente'])->format('d/m/Y') }}
        &nbsp; | &nbsp; Présentation : Franc congolais (CDF)
    </p>

    @foreach(['actif'=>'ACTIF','passif'=>'PASSIF'] as $sens=>$titre)
        <table>
            <thead>
                <tr><th colspan="4">{{ $titre }}</th></tr>
                <tr><th>Réf.</th><th>Libellé</th><th>Exercice N</th><th>Exercice N-1</th></tr>
            </thead>
            <tbody>
                @foreach($etats['bilan'][$sens] as $section)
                    <tr class="section">
                        <td colspan="2">{{ $section['label'] }}</td>
                        <td class="right">{{ number_format($section['total_actuel'],2,',',' ') }}</td>
                        <td class="right">{{ number_format($section['total_precedent'],2,',',' ') }}</td>
                    </tr>
                    @foreach($section['lignes'] as $ligne)
                        <tr>
                            <td>{{ $ligne['code'] }}</td><td>{{ $ligne['label'] }}</td>
                            <td class="right {{ (float)$ligne['actuel'] < 0 ? 'bad' : '' }}">{{ number_format((float)$ligne['actuel'],2,',',' ') }}</td>
                            <td class="right {{ (float)$ligne['precedent'] < 0 ? 'bad' : '' }}">{{ number_format((float)$ligne['precedent'],2,',',' ') }}</td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="total">
                    <td colspan="2">TOTAL {{ $titre }}</td>
                    <td class="right">{{ number_format($etats['bilan']['total_'.$sens],2,',',' ') }}</td>
                    <td class="right">{{ number_format($etats['bilan']['total_'.$sens.'_precedent'],2,',',' ') }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <p class="{{ $etats['bilan']['equilibre']?'ok':'bad' }}">
        <b>Exercice N — Actif {{ number_format($etats['bilan']['total_actif'],2,',',' ') }} | Passif {{ number_format($etats['bilan']['total_passif'],2,',',' ') }} | Écart {{ number_format($etats['bilan']['ecart'],2,',',' ') }} | {{ $etats['bilan']['equilibre']?'Équilibré':'Non équilibré' }}</b>
    </p>
    <p class="{{ $etats['bilan']['equilibre_precedent']?'ok':'bad' }}">
        <b>Exercice N-1 — Actif {{ number_format($etats['bilan']['total_actif_precedent'],2,',',' ') }} | Passif {{ number_format($etats['bilan']['total_passif_precedent'],2,',',' ') }} | Écart {{ number_format($etats['bilan']['ecart_precedent'],2,',',' ') }} | {{ $etats['bilan']['equilibre_precedent']?'Équilibré':'Non équilibré' }}</b>
    </p>

    @foreach(['anomalies'=>'Anomalies du plan comptable','non_classes'=>'Comptes non classés'] as $cle=>$titre)
        @if($etats[$cle])
            <h3 class="bad">{{ $titre }}</h3>
            <table><tr><th>Compte</th><th>Désignation</th><th>Nature</th><th>Observation</th><th>Débit CDF</th><th>Crédit CDF</th><th>Solde CDF</th><th>Raison</th></tr>
                @foreach($etats[$cle] as $c)
                    <tr><td>{{ $c['compte'] }}</td><td>{{ $c['designation'] }}</td><td>{{ $c['nature'] }}</td><td>{{ $c['observation'] }}</td><td class="right">{{ number_format($c['debit'],2,',',' ') }}</td><td class="right">{{ number_format($c['credit'],2,',',' ') }}</td><td class="right">{{ number_format($c['solde'],2,',',' ') }}</td><td>{{ $c['raison'] }}</td></tr>
                @endforeach
            </table>
        @endif
    @endforeach
    @isset($signaturesReleve)@include('exports._releve_signatures')@endisset
</body>
</html>
