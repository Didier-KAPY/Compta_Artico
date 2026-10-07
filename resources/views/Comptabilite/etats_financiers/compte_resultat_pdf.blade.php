<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page{size:A4 landscape;margin:12mm}
        body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#17251f}
        h2,h3,p{text-align:center;margin:4px}
        table{width:100%;border-collapse:collapse;margin:10px 0}
        th,td{border:1px solid #cfd8d3;padding:4px;vertical-align:top}
        th{background:#176b4d;color:#fff;text-align:left}
        .right{text-align:right}.section,.total{font-weight:bold;background:#e8efeb}
        .bad{color:#a92323}.company-header td{border:0!important;background:#fff!important}
    </style>
</head>
<body>
    @include('exports._company_header', ['logoSrc' => $logoPdfSource ?? null])
    <h2>Compte de résultat</h2>
    <p>
        Exercice N : {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
        &nbsp; | &nbsp;
        Exercice N-1 : {{ \Carbon\Carbon::parse($etats['date_debut_precedente'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($etats['date_fin_precedente'])->format('d/m/Y') }}
        &nbsp; | &nbsp; Présentation : Franc congolais (CDF)
    </p>
    <table>
        <thead><tr><th>Réf.</th><th>Libellé</th><th>Exercice N</th><th>Exercice N-1</th></tr></thead>
        <tbody>
            @foreach(['produits_exploitation','charges_exploitation','produits_financiers','charges_financieres','produits_hao','charges_hao','impot_resultat'] as $section)
                <tr class="section">
                    <td></td><td>{{ $etats['compte_resultat'][$section]['label'] }}</td>
                    <td class="right">{{ number_format($etats['compte_resultat'][$section]['total_actuel'],2,',',' ') }}</td>
                    <td class="right">{{ number_format($etats['compte_resultat'][$section]['total_precedent'],2,',',' ') }}</td>
                </tr>
                @foreach($etats['compte_resultat'][$section]['lignes'] as $ligne)
                    <tr>
                        <td>{{ $ligne['code'] }}</td><td>{{ $ligne['label'] }}</td>
                        <td class="right">{{ number_format($ligne['actuel'],2,',',' ') }}</td>
                        <td class="right">{{ number_format($ligne['precedent'],2,',',' ') }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="total">
                <td></td>
                <td>RÉSULTAT NET — N : {{ $etats['compte_resultat']['resultat_net']['actuel']>0?'Bénéfice':($etats['compte_resultat']['resultat_net']['actuel']<0?'Perte':'Nul') }} | N-1 : {{ $etats['compte_resultat']['resultat_net']['precedent']>0?'Bénéfice':($etats['compte_resultat']['resultat_net']['precedent']<0?'Perte':'Nul') }}</td>
                <td class="right">{{ number_format($etats['compte_resultat']['resultat_net']['actuel'],2,',',' ') }}</td>
                <td class="right">{{ number_format($etats['compte_resultat']['resultat_net']['precedent'],2,',',' ') }}</td>
            </tr>
        </tbody>
    </table>
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
