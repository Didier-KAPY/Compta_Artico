<table class="company-header" style="width:100%;border-collapse:collapse;border-bottom:2px solid #176b4d;margin-bottom:10px;">
    <tr>
        <td style="width:72px;text-align:center;vertical-align:middle;border:0;padding:4px;">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="Logo {{ $entreprise?->nom_entreprise ?? 'Entreprise' }}" style="max-width:58px;max-height:58px;">
            @endif
        </td>
        <td style="text-align:center;vertical-align:middle;border:0;padding:4px;">
            <div style="font-size:15px;font-weight:bold;text-transform:uppercase;">
                {{ $entreprise?->nom_entreprise ?? 'Entreprise' }}@include('partials.entreprise-identifiants')
            </div>
            @if($entreprise?->slogan)<div style="font-size:10px;font-style:italic;font-weight:bold;margin-top:3px;">{{ $entreprise->slogan }}</div>@endif
            <div style="font-size:9px;margin-top:3px;">
                {{ $entreprise?->adresse }}
                @if($entreprise?->telephone) — Tél. {{ $entreprise->telephone }}@endif
            </div>
        </td>
        <td style="width:72px;border:0;padding:4px;"></td>
    </tr>
</table>
