<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>État de besoin {{ $etat->numero }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        .actions { margin: 0 auto 18px; max-width: 900px; display: flex; justify-content: flex-end; gap: 8px; }
        .actions a, .actions button { padding: 9px 14px; border: 0; border-radius: 5px; color: #fff; background: #1d4ed8; text-decoration: none; cursor: pointer; }
        .actions a { background: #475569; }
        .sheet { width: 210mm; min-height: 297mm; margin: auto; padding: 18mm 14mm; background: #fff; }
        .pdf-mode .sheet { width: auto; min-height: auto; padding: 0; }
        .header { border-bottom: 3px solid #173f73; padding-bottom: 12px; margin-bottom: 18px; text-align: center; }
        .logo { display: block; max-width: 85px; max-height: 85px; margin: 0 auto 7px; }
        .company { font-size: 20px; font-weight: bold; color: #173f73; text-transform: uppercase; }
        .slogan { margin-top: 3px; font-style: italic; font-weight: bold; color: #334155; }
        .contact { color: #64748b; margin-top: 4px; }
        h1 { margin: 18px 0 4px; text-align: center; font-size: 21px; text-transform: uppercase; }
        .number { text-align: center; color: #475569; margin-bottom: 18px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .meta td { width: 50%; padding: 7px 9px; border: 1px solid #d8dee8; }
        .label { color: #64748b; font-size: 10px; text-transform: uppercase; }
        .value { display: block; margin-top: 2px; font-weight: bold; }
        table.lines { width: 100%; border-collapse: collapse; }
        .lines th { padding: 8px; color: #fff; background: #173f73; border: 1px solid #173f73; }
        .lines td { padding: 8px; border: 1px solid #cbd5e1; }
        .right { text-align: right; }
        .center { text-align: center; }
        .total td { font-weight: bold; background: #edf3fa; }
        .observation { margin-top: 18px; padding: 10px; border: 1px solid #cbd5e1; min-height: 55px; }
        .signatures { width: 100%; margin-top: 45px; }
        .signatures td { width: 33.33%; text-align: center; vertical-align: top; height: 70px; }
        .footer { margin-top: 25px; padding-top: 8px; border-top: 1px solid #d8dee8; color: #64748b; text-align: center; font-size: 9px; }
        @media screen { body { padding: 16px; background: #e5e7eb; } .sheet { box-shadow: 0 4px 20px rgba(15, 23, 42, .18); } }
        @media print { body { background: #fff; } .actions { display: none; } .sheet { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; } }
    </style>
</head>
<body class="{{ $pdfMode ? 'pdf-mode' : '' }}">
@unless($pdfMode)
    <div class="actions">
        <a href="{{ route('etat-besoins.index') }}">Retour</a>
        <a href="{{ route('etat-besoins.pdf', $etat->id) }}">Télécharger PDF</a>
        <button type="button" onclick="window.print()">Imprimer</button>
    </div>
@endunless
<div class="sheet">
    <div class="header">
        @if($logoData)<img class="logo" src="{{ $logoData }}" alt="Logo">@endif
        <div class="company">{{ $entrepriseDocument?->nom_entreprise ?? 'COMPTA ARTICO' }}</div>
        @if($entreprise?->slogan)<div class="slogan">{{ $entreprise->slogan }}</div>@endif
        <div class="contact">{{ $entrepriseDocument?->adresse }} @if($entrepriseDocument?->telephone) — Tél. {{ $entrepriseDocument->telephone }} @endif</div>
    </div>

    <h1>État de besoin</h1>
    <div class="number">N° {{ $etat->numero }}</div>

    <table class="meta">
        <tr><td><span class="label">Date</span><span class="value">{{ \Carbon\Carbon::parse($etat->date)->format('d/m/Y') }}</span></td><td><span class="label">Statut</span><span class="value">{{ $etat->statut }}</span></td></tr>
        <tr><td><span class="label">Département</span><span class="value">{{ $etat->departement?->designation ?? $etat->service }}</span></td><td><span class="label">Demandeur</span><span class="value">{{ $etat->demandeur }}</span></td></tr>
        <tr><td colspan="2"><span class="label">Motif</span><span class="value">{{ $etat->motif }}</span></td></tr>
    </table>

    <table class="lines">
        <thead><tr><th>Désignation</th><th>Quantité</th><th>Prix unitaire</th><th>Montant</th></tr></thead>
        <tbody>
        @forelse($etat->lignes as $ligne)
            <tr><td>{{ $ligne->designation }}</td><td class="center">{{ number_format($ligne->quantite, 2, ',', ' ') }}</td><td class="right">{{ number_format($ligne->prix_unitaire, 2, ',', ' ') }} {{ $etat->monnaie }}</td><td class="right">{{ number_format($ligne->montant, 2, ',', ' ') }} {{ $etat->monnaie }}</td></tr>
        @empty
            <tr><td colspan="4" class="center">Aucune ligne enregistrée.</td></tr>
        @endforelse
            <tr class="total"><td colspan="3" class="right">TOTAL ESTIMÉ</td><td class="right">{{ number_format($etat->montant_estime, 2, ',', ' ') }} {{ $etat->monnaie }}</td></tr>
        </tbody>
    </table>

    <div class="observation"><span class="label">Observation</span><br>{{ $etat->observation ?: '—' }}</div>
    <table class="signatures"><tr><td>Le demandeur<br><br><br>____________________</td><td>Le responsable<br><br><br>____________________</td><td>La direction<br><br><br>____________________</td></tr></table>
    <div class="footer">Document généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>
</body>
</html>
