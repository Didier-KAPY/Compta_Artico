<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<title>Reçu</title>
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
    .receipt{
        width:80mm;
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
    }
    .total{
        font-size:15px;
        font-weight:bold;
    }
    .logo{
        max-width:60px;
        margin-bottom:5px;
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
</style>
</head>

<body>
<div class="receipt">
    {{-- ENTETE --}}
    <div class="text-center">
        @if(isset($entreprise) && $entreprise->logo)
            <img src="{{ asset('storage/'.$entreprise->logo) }}"
                 class="logo">
        @endif
        <div class="bold">
            {{ $entreprise->nom_entreprise ?? 'DOXA SERVICES' }}
        </div>
        <div>
            L'excellence au service
        </div>
        <div>
            Kinshasa - RDC
        </div>
    </div>
    <div class="line"></div>
    <div class="text-center bold">
        REÇU DE CAISSE
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
            <td>Client :</td>
            <td class="text-right">
                {{ $journal->noms_client }}
            </td>
        </tr>
        <tr>
            <td>Tél :</td>
            <td class="text-right">
                {{ $journal->telephone }}
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
                {{ ucfirst($journal->mode_paiement) }}
            </td>
        </tr>
        <tr>
            <td>Type :</td>
            <td class="text-right">
                {{ ucfirst($journal->type) }}
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
            {{ auth()->user()->nom ?? 'Nom caissier' }}
        </td>
        <td class="text-center">
            <strong>
                Client
            </strong>
            <br><br><br>
            __________________
            <br>
            {{ $journal->noms_client ?? '' }}
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

<script>

    window.onload=function(){
        window.print();
    }
</script>

</body>
</html>