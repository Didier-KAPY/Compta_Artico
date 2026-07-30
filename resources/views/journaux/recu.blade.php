<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<title>Reçu {{ $journal->reference }}</title>
<style>
    @page{
        margin:5mm;
    }

    body{
        width:80mm;
        margin:auto;
        font-family:'Courier New', monospace;
        font-size:12px;
        color:#000;
    }
    .actions{display:flex;gap:8px;justify-content:center;margin:15px auto;width:80mm;font-family:Arial,sans-serif}
    .actions a,.actions button{padding:8px 12px;border:0;border-radius:5px;text-decoration:none;cursor:pointer;font-size:12px}
    .btn-print{background:#176b4d;color:#fff}.btn-download{background:#dc3545;color:#fff}.btn-back{background:#e9ecef;color:#222}
    .receipt{
        width:80mm;
        margin:0 auto;
        overflow-wrap:anywhere;
        word-wrap:break-word;
    }
    .text-center{
        text-align:center;
    }
    .text-right{
        text-align:right;
    }
    .bold{
        font-weight:bold;
    }
    .line{
        border-top:1px dashed #000;
        margin:8px 0;
    }

    table{
        width:100%;
        border-collapse:collapse;
    }
    td{
        padding:3px 0;
        vertical-align:top;
        overflow-wrap:anywhere;
    }
    .total{
        font-size:15px;
        font-weight:bold;
    }
    .logo{
        max-width:60px;
        max-height:60px;
        margin-bottom:5px;
    }
    .company-name{
        font-size:14px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }
    .slogan{
        margin:3px 0 5px;
        font-size:11px;
        font-style:italic;
        font-weight:bold;
    }

    @media print{
        .no-print{
            display:none;
        }
    }
    .signature{
        margin-top:30px;
        text-align:center;
    }
    .signature-space{
        height:45px;
    }
    .draft{
        margin:8px 0;
        padding:6px;
        border:2px solid #b42318;
        color:#b42318;
        text-align:center;
        font-family:Arial,sans-serif;
        font-weight:bold;
        letter-spacing:1px;
    }
</style>
</head>

<body>
@unless($isPdf ?? false)
<div class="actions no-print">
    <button type="button" class="btn-print" onclick="window.print()">Imprimer</button>
    <a class="btn-download" href="{{ route('journaux.recu.pdf', $journal->id) }}">Télécharger PDF</a>
    <a class="btn-back" href="{{ route('journaux.index') }}">Retour</a>
</div>
@endunless
<div class="receipt">
    {{-- ENTETE --}}
    <div class="text-center">
        @if(isset($entreprise) && $entreprise->logo)
            <img src="{{ ($isPdf ?? false) ? public_path('storage/'.$entreprise->logo) : asset('storage/'.$entreprise->logo) }}" class="logo" alt="Logo">
        @endif
        <div class="bold company-name">
            {{ $entreprise->nom_entreprise ?? 'DOXA SERVICES' }}
        </div>
        <div class="slogan">Découvre ton habilité</div>
        <div>
            {{ $entreprise->adresse ?? 'Kinshasa - RDC' }}
        </div>
        @if($entreprise?->telephone)<div>Tél. {{ $entreprise->telephone }}</div>@endif
    </div>
    <div class="line"></div>
    @if(!in_array(mb_strtolower(trim($journal->statut ?? '')), ['validé', 'valide'], true))
        <div class="draft">PROVISOIRE — NON VALIDÉ</div>
    @endif
    <div class="text-center bold">
        REÇU DE {{ ($journal->entrees_cdf > 0 || $journal->entrees_usd > 0) ? 'RECETTE' : 'PAIEMENT' }}
    </div>
    <div class="line"></div>
    {{-- INFORMATIONS --}}
    <table>
        <tr>
            <td>Date :</td>
            <td class="text-right">
                {{ \Carbon\Carbon::parse($journal->date)->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td>N° :</td>
            <td class="text-right">
                {{ $journal->reference }}
            </td>
        </tr>
        <tr>
            <td>Partenaire :</td>
            <td class="text-right">
                {{ $journal->nom_partenaire ?: 'Non renseigné' }}
            </td>
        </tr>
        <tr>
            <td>Tél :</td>
            <td class="text-right">
                {{ $journal->telephone_partenaire ?: '-' }}
            </td>
        </tr>
    </table>
    <div class="line"></div>
    {{-- DESCRIPTION --}}
    <div class="bold">
        Désignation :
    </div>
    <div>
        {{ $journal->description }}
    </div>

    <div class="line"></div>
    {{-- MONTANTS --}}
    <table>
        <tr>
            <td>Montant HT</td>
            <td class="text-right">
                {{ number_format($journal->montant_ht,2,',',' ') }}
                {{ $journal->monnaie }}
            </td>
        </tr>
        <tr>
            <td>TVA</td>
            <td class="text-right">
                {{ number_format($journal->montant_tva,2,',',' ') }}
                {{ $journal->monnaie }}
            </td>
        </tr>
        <tr class="total">
            <td>TOTAL TTC</td>
            <td class="text-right">
                {{ number_format($journal->montant_ttc,2,',',' ') }}
                {{ $journal->monnaie }}
            </td>
        </tr>
    </table>
    <div class="line"></div>
    <table>
        <tr>
            <td>Paiement :</td>
            <td class="text-right">
                {{ ucfirst(str_replace('_', ' ', $journal->mode_paiement ?? 'Non renseigné')) }}
            </td>
        </tr>
        <tr>
            <td>Type :</td>
            <td class="text-right">
                {{ ucfirst($journal->type ?? 'Opération') }}
            </td>
        </tr>
    </table>

    <div class="line"></div>
    <div class="text-center bold">
        Merci pour votre confiance
    </div>
    <br>
    <table>
    <tr>
        <td class="text-center">
            <strong>
                Caissier
            </strong>
            <br><br><br>
            __________________
            <br>
            {{ trim(($journal->user?->prenom ?? '').' '.($journal->user?->nom ?? '')) ?: 'Utilisateur' }}
        </td>
        <td class="text-center">
            <strong>
                Client
            </strong>
            <br><br><br>
            __________________
            <br>
            {{ $journal->nom_partenaire ?? '' }}
        </td>
    </tr>
    </table>
    <div class="line"></div>
    <div class="text-center">
        Impression :
        <br>
        {{ date('d/m/Y H:i') }}
    </div>
</div>

</body>
</html>
