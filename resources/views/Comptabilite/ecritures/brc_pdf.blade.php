<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<title>
{{ $numeroBrc }}
</title>


<style>


@page {

    margin: 25px;

}



body {

    font-family: DejaVu Sans, sans-serif;

    font-size: 12px;

    color:#000;

}



.text-center {

    text-align:center;

}



.text-right {

    text-align:right;

}



.bold {

    font-weight:bold;

}



table {

    width:100%;

    border-collapse:collapse;

}



table th,
table td {

    border:1px solid #000;

    padding:6px;

}



.no-border td {

    border:none;

}



.logo {

    width:90px;

}



.header-title {

    font-size:18px;

    font-weight:bold;

}



.title {

    font-size:16px;

    font-weight:bold;

    margin-top:15px;

}



.total {

    font-weight:bold;

}



.badge-success {

    color:green;

    font-weight:bold;

}



.badge-danger {

    color:red;

    font-weight:bold;

}



.signature {

    margin-top:60px;

}



</style>


</head>



<body>



{{-- =====================================================
ENTETE ENTREPRISE
===================================================== --}}



<table class="no-border">


<tr>


<td width="20%">


@if($entreprise && $entreprise->logo)


<img src="{{ public_path('storage/'.$entreprise->logo) }}"
class="logo">


@endif


</td>




<td class="text-center">


<div class="header-title">


{{ $entreprise->nom_entreprise ?? 'DOXA SERVICES' }}


</div>



<div>

L'excellence au service

</div>



<div>

Kinshasa - RDC

</div>




<div class="title">


BON DE REGULARISATION COMPTABLE


</div>




<div class="bold">


{{ $numeroBrc }}


</div>



</td>




<td width="20%" class="text-right">


<strong>

Date :

</strong>


<br>


{{ now()->format('d/m/Y') }}



</td>


</tr>


</table>



<br>



<hr>




{{-- =====================================================
INFORMATIONS
===================================================== --}}



<table class="no-border">


<tr>


<td>


<strong>

Période :

</strong>


Du

{{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }}


au


{{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}



</td>




<td class="text-right">


<strong>

Préparé par :

</strong>


{{ auth()->user()->name ?? 'Utilisateur' }}



</td>



</tr>



</table>




<br>





{{-- =====================================================
DETAIL BRC
===================================================== --}}



<table>


<thead>


<tr>


<th>Date</th>


<th>Référence</th>


<th>Compte</th>


<th>Désignation</th>


<th>Débit CDF</th>


<th>Crédit CDF</th>



</tr>


</thead>




<tbody>



@foreach($brc as $ligne)



<tr>


<td>


{{ \Carbon\Carbon::parse(
$ligne['date']
)->format('d/m/Y') }}


</td>



<td>


{{ $ligne['reference'] }}


</td>




<td class="bold">


{{ $ligne['compte'] }}


</td>




<td>


{{ $ligne['designation'] }}


</td>




<td class="text-right">


{{ number_format(
$ligne['debit'],
2,
',',
' '
) }}



</td>




<td class="text-right">


{{ number_format(
$ligne['credit'],
2,
',',
' '
) }}



</td>



</tr>



@endforeach



</tbody>




<tfoot>



<tr class="total">


<td colspan="4"
class="text-right">


TOTAL GENERAL


</td>



<td class="text-right">


{{ number_format(
$totalDebit,
2,
',',
' '
) }}



</td>




<td class="text-right">


{{ number_format(
$totalCredit,
2,
',',
' '
) }}



</td>



</tr>




<tr>


<td colspan="4"
class="text-right bold">


Contrôle équilibre


</td>




<td colspan="2"
class="text-center">


@if($totalDebit == $totalCredit)


<span class="badge-success">


ECRITURE EQUILIBREE


</span>



@else



<span class="badge-danger">


ECART :

{{ number_format(
abs($totalDebit-$totalCredit),
2,
',',
' '
) }}



</span>



@endif



</td>


</tr>



</tfoot>



</table>






{{-- =====================================================
SIGNATURES
===================================================== --}}



<table class="no-border signature">


<tr>



<td class="text-center">


<strong>

Préparé par

</strong>



<br><br><br>



_________________________



<br>



{{ auth()->user()->name ?? 'Utilisateur' }}



</td>




<td class="text-center">


<strong>

Validé par

</strong>



<br><br><br>



_________________________



</td>



</tr>


</table>






<br>



<div class="text-center">


Imprimé le :

{{ date('d/m/Y H:i') }}



</div>




</body>


</html>