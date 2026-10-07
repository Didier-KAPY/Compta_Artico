<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#17251f}
        h1{font-size:18px;margin:10px 0 4px;text-align:center}
        .period{color:#66756e;margin-bottom:16px;text-align:center}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #cfd8d3;padding:6px 5px;vertical-align:top}
        th{background:#176b4d;color:#fff;text-align:left}
        tr:nth-child(even) td{background:#f3f7f5}
        .empty{text-align:center;color:#66756e;padding:18px}
        .footer{margin-top:12px;color:#66756e;text-align:right}
        .company-header td{border:0!important;background:#fff!important}
    </style>
</head>
<body>
    @include('exports._company_header', ['logoSrc' => $logoPdfSource ?? null])
    <h1>{{ $titre }}</h1>
    <div class="period">
        Exercice N : {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
        @if($dateDebutN1 && $dateFinN1)<br>Exercice N-1 : {{ \Carbon\Carbon::parse($dateDebutN1)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFinN1)->format('d/m/Y') }}@endif
        @isset($compteSelectionne)<br><strong>Compte sélectionné : {{ $compteSelectionne }}</strong>@endisset
    </div>
    <table>
        <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) }}" class="empty">Aucune donnée pour cette période.</td></tr>
            @endforelse
        </tbody>
    </table>
    @isset($signaturesReleve)@include('exports._releve_signatures')@endisset
    <div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</body>
</html>
