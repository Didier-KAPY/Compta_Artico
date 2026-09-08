<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page{size:A4 portrait;margin:12mm}
        body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#172033}
        .header{text-align:center;border-bottom:3px solid #176b4d;padding-bottom:8px;margin-bottom:10px}
        .logo{display:block;max-width:55px;max-height:55px;margin:0 auto 5px}
        .company{font-size:17px;font-weight:bold;color:#176b4d;text-transform:uppercase}
        .slogan{font-style:italic;font-weight:bold;margin:2px 0}
        .contact{color:#64748b}
        h2,p{text-align:center;margin:4px}
        h2{text-transform:uppercase;margin-top:12px}
        table{width:100%;border-collapse:collapse;margin:9px 0}
        th,td{border:1px solid #333;padding:3px}
        .right{text-align:right}
        .section,.total{font-weight:bold;background:#ddd}
        .summary{font-weight:bold;padding:7px;border:1px solid #bbb}
        .ok{color:green}.bad{color:#b91c1c}
        .approval{width:100%;margin-top:28px;page-break-inside:avoid}
        .approval td{width:33.33%;border:0;text-align:center;vertical-align:top;font-weight:bold}
        .approval img{display:block;width:150px;height:70px;object-fit:contain;margin:7px auto 2px}
    </style>
</head>
<body>
    <div class="header">
        @if($entreprise?->logo && file_exists(public_path('storage/'.$entreprise->logo)))
            <img class="logo" src="{{ public_path('storage/'.$entreprise->logo) }}" alt="Logo">
        @endif
        <div class="company">{{ $entreprise?->nom_entreprise ?? 'COMPTA ARTICO' }}</div>
        @if($entreprise?->slogan)<div class="slogan">{{ $entreprise->slogan }}</div>@endif
        @if($entreprise?->adresse || $entreprise?->telephone)
            <div class="contact">{{ $entreprise?->adresse }} @if($entreprise?->telephone) — Tél. {{ $entreprise->telephone }} @endif</div>
        @endif
    </div>
    <h2>Bilan d’ouverture</h2>
    <p><strong>{{ $bilanInitial->libelle }}</strong></p>
    <p>Devise de présentation : Franc congolais (CDF)</p>
    <p>Date : 01/08/2026</p>
    <p>Date d’archivage : {{ $bilanInitial->created_at->format('d/m/Y') }}</p>

    @foreach(['actif' => 'ACTIF', 'passif' => 'PASSIF'] as $sens => $titre)
        <table>
            <tr class="total"><th colspan="3">{{ $titre }}</th></tr>
            <tr><th>Réf.</th><th>Libellé</th><th>Exercice N</th></tr>
            @foreach($etats['bilan'][$sens] as $section)
                <tr class="section"><td colspan="2">{{ $section['label'] }}</td><td class="right">{{ number_format($section['total_actuel'], 2, ',', ' ') }}</td></tr>
                @foreach($section['lignes'] as $ligne)
                    <tr><td>{{ $ligne['code'] }}</td><td>{{ $ligne['label'] }}</td><td class="right">{{ number_format(abs((float) $ligne['actuel']), 2, ',', ' ') }}</td></tr>
                @endforeach
            @endforeach
            <tr class="total"><td colspan="2">TOTAL {{ $titre }}</td><td class="right">{{ number_format($etats['bilan']['total_'.$sens], 2, ',', ' ') }}</td></tr>
        </table>
    @endforeach

    <p class="summary {{ $etats['bilan']['equilibre'] ? 'ok' : 'bad' }}">
        Total actif : {{ number_format($etats['bilan']['total_actif'], 2, ',', ' ') }} —
        Total passif : {{ number_format($etats['bilan']['total_passif'], 2, ',', ' ') }} —
        Écart : {{ number_format($etats['bilan']['ecart'], 2, ',', ' ') }} —
        {{ $etats['bilan']['equilibre'] ? 'Équilibré' : 'Non équilibré' }}
    </p>

    <table class="approval">
        <tr>
            <td>
                Signature du chargé des finances
                @if($chargeFinances?->signature && file_exists(public_path('storage/'.$chargeFinances->signature)))
                    <img src="{{ public_path('storage/'.$chargeFinances->signature) }}" alt="Signature du chargé des finances">
                @endif
                @if($chargeFinances)<div>{{ trim($chargeFinances->prenom.' '.$chargeFinances->nom) }}</div>@endif
            </td>
            <td>
                Cachet de l’entreprise
                @if($entreprise?->cachet && file_exists(public_path('storage/'.$entreprise->cachet)))
                    <img src="{{ public_path('storage/'.$entreprise->cachet) }}" alt="Cachet de l’entreprise">
                @endif
            </td>
            <td>
                Signature du gérant
                @if($gerant?->signature && file_exists(public_path('storage/'.$gerant->signature)))
                    <img src="{{ public_path('storage/'.$gerant->signature) }}" alt="Signature du gérant">
                @endif
                @if($gerant)<div>{{ trim($gerant->prenom.' '.$gerant->nom) }}</div>@endif
            </td>
        </tr>
    </table>
</body>
</html>
