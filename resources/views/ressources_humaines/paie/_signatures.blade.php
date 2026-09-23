<table style="width:100%; margin-top:24px; page-break-inside:avoid">
    <tr>
<td style="width:33%;text-align:center;vertical-align:top">Nom de l’employé<br>{{ trim(($paie->employe?->nom ?? '').' '.($paie->employe?->postnom ?? '').' '.($paie->employe?->prenom ?? '')) }}</td>

        {{-- Cachet de l'entreprise --}}
        <td style="width:34%; text-align:center; vertical-align:top">
            Cachet de l’entreprise

            <div style="height:85px; padding-top:8px">
                @if ($entreprise && $entreprise->cachet && is_file(public_path('storage/' . $entreprise->cachet)))
                    <img
                        src="{{ public_path('storage/' . $entreprise->cachet) }}"
                        alt="Cachet de l’entreprise"
                        style="max-width:140px; max-height:75px"
                    >
                @endif
            </div>
        </td>

        {{-- Signature du gérant --}}
        <td style="width:33%; text-align:center; vertical-align:top">
            Signature du gérant

            <div style="height:85px; padding-top:8px">
                @if ($gerant && $gerant->signature && is_file(public_path('storage/' . $gerant->signature)))
                    <img
                        src="{{ public_path('storage/' . $gerant->signature) }}"
                        alt="Signature du gérant"
                        style="max-width:140px; max-height:75px"
                    >
                @endif
            </div>

            @if ($gerant)
                {{ $gerant->nom }} {{ $gerant->prenom }}
            @else
                ____________________
            @endif
        </td>
    </tr>
</table>
