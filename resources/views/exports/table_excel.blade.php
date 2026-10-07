<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body>
    @include('exports._company_header', ['logoSrc' => $logoUrl ?? null])
    <h2 style="text-align:center">{{ $titre }}</h2>
    <p style="text-align:center">
        Exercice N : {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
        @if($dateDebutN1 && $dateFinN1)<br>Exercice N-1 : {{ \Carbon\Carbon::parse($dateDebutN1)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFinN1)->format('d/m/Y') }}@endif
        @isset($compteSelectionne)<br><strong>Compte sélectionné : {{ $compteSelectionne }}</strong>@endisset
    </p>
    <table border="1" style="border-collapse:collapse;width:100%">
        <thead><tr>@foreach($headers as $header)<th style="background:#176b4d;color:#fff">{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) }}">Aucune donnée pour cette période.</td></tr>
            @endforelse
        </tbody>
    </table>
    @isset($signaturesReleve)@include('exports._releve_signatures')@endisset
</body>
</html>
