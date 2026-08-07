<table border="1">
    <tr><th colspan="4" style="font-size:18px">{{ $entreprise?->nom_entreprise ?? 'COMPTA ARTICO' }}</th></tr>
    <tr><th colspan="4" style="font-size:16px">BON DE RÉGULARISATION COMPTABLE</th></tr>
    <tr><th>Référence</th><td>{{ $brc->reference }}</td><th>Date</th><td>{{ $brc->date?->format('d/m/Y') }}</td></tr>
    <tr><th>Journal</th><td>{{ $brc->journalType?->code }} — {{ $brc->journalType?->libelle }}</td><th>Statut</th><td>{{ $brc->statut }}</td></tr>
    <tr><th>Monnaie</th><td>{{ $brc->monnaie }}</td><th>Sens</th><td>{{ $brc->sens === 'debit' ? 'Débit' : 'Crédit' }}</td></tr>
    <tr><td colspan="4"></td></tr>
    <tr style="font-weight:bold;background:#dbeafe"><th>N°</th><th>Compte</th><th>Libellé</th><th>Montant</th></tr>
    @foreach($brc->lignes as $ligne)
        <tr><td>{{ $loop->iteration }}</td><td>{{ $ligne->compte?->compte }}</td><td>{{ $ligne->libelle }}</td><td>{{ number_format($ligne->montant, 2, ',', ' ') }}</td></tr>
    @endforeach
    <tr style="font-weight:bold"><td colspan="3">TOTAL</td><td>{{ number_format($brc->total, 2, ',', ' ') }} {{ $brc->monnaie }}</td></tr>
</table>
