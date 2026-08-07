<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BRC {{ $brc->reference }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        .sheet { width: 100%; background: #fff; }
        .header { border-bottom: 3px solid #173f73; padding-bottom: 12px; margin-bottom: 18px; text-align: center; }
        .logo { display: block; max-width: 85px; max-height: 85px; margin: 0 auto 7px; }
        .company { font-size: 20px; font-weight: bold; color: #173f73; text-transform: uppercase; }
        .slogan { margin-top: 3px; font-style: italic; font-weight: bold; color: #334155; }
        .contact { color: #64748b; margin-top: 4px; }
        h1 { margin: 18px 0 4px; text-align: center; font-size: 21px; text-transform: uppercase; }
        .number { text-align: center; color: #475569; margin-bottom: 18px; }
        .meta, .lines, .signatures { width: 100%; border-collapse: collapse; }
        .meta { margin-bottom: 16px; }
        .meta td { width: 50%; padding: 7px 9px; border: 1px solid #d8dee8; }
        .label { color: #64748b; font-size: 10px; text-transform: uppercase; }
        .value { display: block; margin-top: 2px; font-weight: bold; }
        .lines th { padding: 8px; color: #fff; background: #173f73; border: 1px solid #173f73; }
        .lines td { padding: 8px; border: 1px solid #cbd5e1; }
        .right { text-align: right; } .center { text-align: center; }
        .total td { font-weight: bold; background: #edf3fa; }
        .signatures { margin-top: 45px; }
        .signatures td { width: 33.33%; text-align: center; vertical-align: top; height: 70px; }
        .footer { margin-top: 25px; padding-top: 8px; border-top: 1px solid #d8dee8; color: #64748b; text-align: center; font-size: 9px; }
    </style>
</head>
<body class="{{ $pdfMode ? 'pdf-mode' : '' }}">
<div class="sheet">
    <div class="header">
        @if($logoData)<img class="logo" src="{{ $logoData }}" alt="Logo">@endif
        <div class="company">{{ $entreprise?->nom_entreprise ?? 'COMPTA ARTICO' }}</div>
        @if($entreprise?->slogan)<div class="slogan">{{ $entreprise->slogan }}</div>@endif
        <div class="contact">{{ $entreprise?->adresse }} @if($entreprise?->telephone) — Tél. {{ $entreprise->telephone }} @endif</div>
    </div>
    <h1>Bon de régularisation comptable</h1>
    <div class="number">N° {{ $brc->reference }}</div>
    <table class="meta">
        <tr><td><span class="label">Date</span><span class="value">{{ $brc->date?->format('d/m/Y') }}</span></td><td><span class="label">Statut</span><span class="value">{{ $brc->statut }}</span></td></tr>
        <tr><td><span class="label">Journal</span><span class="value">{{ $brc->journalType?->code }} — {{ $brc->journalType?->libelle }}</span></td><td><span class="label">Monnaie / sens</span><span class="value">{{ $brc->monnaie }} — {{ $brc->sens === 'debit' ? 'Débit' : 'Crédit' }}</span></td></tr>
    </table>
    <table class="lines">
        <thead><tr><th>N°</th><th>Compte</th><th>Libellé</th><th>Montant</th></tr></thead>
        <tbody>
        @forelse($brc->lignes as $ligne)
            <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $ligne->compte?->compte }}</td><td>{{ $ligne->libelle }}</td><td class="right">{{ number_format($ligne->montant, 2, ',', ' ') }} {{ $brc->monnaie }}</td></tr>
        @empty
            <tr><td colspan="4" class="center">Aucune ligne enregistrée.</td></tr>
        @endforelse
            <tr class="total"><td colspan="3" class="right">TOTAL</td><td class="right">{{ number_format($brc->total, 2, ',', ' ') }} {{ $brc->monnaie }}</td></tr>
        </tbody>
    </table>
    <table class="signatures"><tr><td>Établi par<br><br><br>____________________</td><td>Le comptable<br><br><br>____________________</td><td>La direction<br><br><br>____________________</td></tr></table>
    <div class="footer">Document généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>
</body>
</html>
