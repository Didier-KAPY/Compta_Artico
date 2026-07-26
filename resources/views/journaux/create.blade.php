@extends('layouts.app')

@section('content')

<style>

body{
    background:#f8f9fa;
}

.form-container{
    max-width:1200px;
    margin:auto;
}

.card{
    border-radius:15px;
    border:0;
}

.card-header{
    border-radius:15px 15px 0 0 !important;
    font-weight:700;
}

.form-label{
    font-weight:600;
    color:#344767;
}

.form-control,
.form-select{
    height:42px;
    border-radius:8px;
}

textarea.form-control{
    height:auto;
}

.btn{
    border-radius:8px;
}

.taux-box{
    text-align:center;
    background:white;
    padding:20px;
    border-radius:15px;
}

.taux-value{
    font-size:25px;
    font-weight:800;
    color:#198754;
}

</style>



<div class="container-fluid py-4">

<div class="form-container">


<div class="card shadow-sm mb-4">

<div class="card-body d-flex justify-content-between align-items-center">


<div>

<h3 class="fw-bold text-primary">

<i class="bi bi-journal-plus"></i>

Nouvelle écriture comptable

</h3>


<small class="text-muted">
Saisie automatique en partie double
</small>

</div>



<a href="{{ route('journaux.index') }}"
class="btn btn-outline-secondary">

<i class="bi bi-arrow-left"></i>
Retour

</a>


</div>

</div>



@if(session('success'))

<div class="alert alert-success">

{{ session('success') }}

</div>

@endif



@if($errors->any())

<div class="alert alert-danger">

<ul class="mb-0">

@foreach($errors->all() as $error)

<li>{{ $error }}</li>

@endforeach

</ul>

</div>

@endif





<form action="{{ route('journaux.store') }}"
method="POST"
enctype="multipart/form-data">

@csrf




{{-- TAUX DE CHANGE --}}


<div class="card shadow-sm mb-4">


<div class="card-body taux-box">


<h5 class="text-primary fw-bold">

<i class="bi bi-currency-exchange"></i>

Taux de change

</h5>



@if($taux)

<div class="taux-value">

1 USD =
{{ number_format($taux->taux_de_change,2) }}
CDF

</div>

@else

<div class="text-danger">

Aucun taux défini

</div>

@endif


</div>

</div>






{{-- INFORMATIONS JOURNAL --}}


<div class="card shadow-sm mb-4">


<div class="card-header bg-primary text-white">


<i class="bi bi-journal-text"></i>

Informations journal


</div>



<div class="card-body">


<div class="row g-3">





<div class="col-md-5">


<label class="form-label">

Journal *

</label>


<select name="journal_type_id"
class="form-select"
required>


<option value="">

-- Choisir journal --

</option>



@foreach($journalTypes as $journal)


<option value="{{ $journal->id }}"

{{ old('journal_type_id')==$journal->id?'selected':'' }}

>


{{ $journal->code }}
-
{{ $journal->libelle }}


@if($journal->compte)

({{ $journal->compte->compte }})

@endif


</option>


@endforeach


</select>


</div>






<div class="col-md-3">


<label class="form-label">

Date *

</label>


<input type="date"

name="date"

class="form-control"

value="{{ old('date',date('Y-m-d')) }}"

required>


</div>






<div class="col-md-2">


<label class="form-label">

Type *

</label>



<select name="type"

class="form-select"

required>



<option value="recette">

Recette

</option>



<option value="depense">

Dépense

</option>



<option value="achat">

Achat

</option>



<option value="vente">

Vente

</option>



<option value="od">

Opération diverse

</option>



</select>



</div>






<div class="col-md-2">


<label class="form-label">

Monnaie *

</label>


<select name="monnaie"

class="form-select"

required>


<option value="CDF">

CDF

</option>


<option value="USD">

USD

</option>


</select>


</div>



</div>


</div>

</div>








{{-- COMPTE OPERATION --}}


<div class="card shadow-sm mb-4">


<div class="card-header bg-light">


<i class="bi bi-list-check"></i>

Compte mouvementé


</div>




<div class="card-body">


<label class="form-label">

Compte d'opération *

</label>



<select name="liste_des_comptes_id"

class="form-select"

required>


<option value="">

-- Sélectionner un compte --

</option>



@foreach($comptes as $compte)


<option value="{{ $compte->id }}"

{{ old('liste_des_comptes_id')==$compte->id?'selected':'' }}

>


{{ $compte->compte }}

-

{{ $compte->designation }}


</option>


@endforeach



</select>


</div>


</div>






{{-- PARTENAIRE --}}


<div class="card shadow-sm mb-4">


<div class="card-header bg-light">


<i class="bi bi-person"></i>

Partenaire


</div>



<div class="card-body">


<div class="row g-3">



<div class="col-md-4">


<label class="form-label">

Nom *

</label>


<input type="text"

name="nom_partenaire"

class="form-control"

value="{{ old('nom_partenaire') }}"

required>


</div>





<div class="col-md-4">


<label class="form-label">

Téléphone *

</label>


<input type="text"

name="telephone_partenaire"

class="form-control"

value="{{ old('telephone_partenaire') }}"

required>


</div>





<div class="col-md-4">


<label class="form-label">

Adresse *

</label>


<input type="text"

name="adresse_partenaire"

class="form-control"

value="{{ old('adresse_partenaire') }}"

required>


</div>


</div>


</div>

</div>





{{-- DESCRIPTION --}}


<div class="card shadow-sm mb-4">


<div class="card-header bg-light">


<i class="bi bi-card-text"></i>

Libellé opération


</div>



<div class="card-body">


<textarea

name="description"

rows="3"

class="form-control"

required>{{ old('description') }}</textarea>


</div>


</div>





{{-- MONTANTS --}}


<div class="card shadow-sm mb-4">


<div class="card-header bg-success text-white">


<i class="bi bi-calculator"></i>

Montants


</div>



<div class="card-body">


<div class="row g-3">



<div class="col-md-4">


<label class="form-label">

Mode paiement *

</label>



<select name="mode_paiement"

class="form-select"

required>


<option value="espece">

Espèces

</option>


<option value="banque">

Banque

</option>


<option value="mobile_money">

Mobile Money

</option>


</select>


</div>





<div class="col-md-4">


<label class="form-label">

Montant TTC *

</label>


<input type="number"

step="0.01"

name="montant_ttc"

id="montant_ttc"

class="form-control"

required>


</div>




<div class="col-md-4">


<label class="form-label">

Taux TVA %

</label>


<input type="number"

name="taux_tva"

id="taux_tva"

value="16"

class="form-control">


</div>


</div>



<div class="row mt-3 g-3">


<div class="col-md-6">


<label class="form-label">

Montant HT

</label>


<input type="text"

id="montant_ht"

class="form-control"

readonly>


</div>



<div class="col-md-6">


<label class="form-label">

Montant TVA

</label>


<input type="text"

id="montant_tva"

class="form-control"

readonly>


</div>


</div>



</div>

</div>
{{-- PIECE JUSTIFICATIVE --}}


<div class="card shadow-sm mb-4">


<div class="card-header bg-light">

<i class="bi bi-paperclip"></i>

Pièce justificative

</div>



<div class="card-body">


<input type="file"

name="piece_justificatif"

class="form-control">


<small class="text-muted">

PDF, JPG, PNG acceptés

</small>


</div>


</div>







{{-- APERCU ECRITURE --}}


<div class="card shadow-sm mb-4">


<div class="card-header bg-dark text-white">


<i class="bi bi-journal-check"></i>

Aperçu de l'écriture comptable


</div>




<div class="card-body">


<div class="table-responsive">


<table class="table table-bordered">


<thead class="table-light">


<tr>

<th>
Compte
</th>


<th>
Débit
</th>


<th>
Crédit
</th>


</tr>


</thead>



<tbody id="apercu_ecriture">


<tr>


<td colspan="3"

class="text-center text-muted">


Complétez les informations


</td>


</tr>


</tbody>





<tfoot>


<tr class="fw-bold">


<td>

TOTAL

</td>


<td id="total_debit">

0.00

</td>


<td id="total_credit">

0.00

</td>


</tr>


</tfoot>



</table>


</div>


</div>


</div>







{{-- BOUTONS --}}


<div class="card shadow-sm mb-4">


<div class="card-body">


<div class="d-flex justify-content-between">


<a href="{{ route('journaux.index') }}"

class="btn btn-outline-secondary">


<i class="bi bi-x-circle"></i>

Annuler


</a>




<button type="submit"

class="btn btn-primary">


<i class="bi bi-check-circle"></i>

Enregistrer


</button>



</div>


</div>


</div>




</form>


</div>

</div>

</div>







<script>


/*
|--------------------------------------------------------------------------
| CALCUL TVA
|--------------------------------------------------------------------------
*/


function calculMontant(){


let ttc =
parseFloat(
document.getElementById('montant_ttc').value
)
||0;



let taux =
parseFloat(
document.getElementById('taux_tva').value
)
||0;



let ht = 0;

let tva = 0;



if(ttc > 0){


ht =
ttc /
(1+(taux/100));



tva =
ttc-ht;


}



document.getElementById('montant_ht').value =
ht.toFixed(2);



document.getElementById('montant_tva').value =
tva.toFixed(2);



genererApercu();


}





document
.getElementById('montant_ttc')
.addEventListener(
'input',
calculMontant
);



document
.getElementById('taux_tva')
.addEventListener(
'input',
calculMontant
);








/*
|--------------------------------------------------------------------------
| APERCU COMPTABLE
|--------------------------------------------------------------------------
*/


function genererApercu(){



let montantTTC =

parseFloat(
document.getElementById('montant_ttc').value
)
||0;



let montantHT =

parseFloat(
document.getElementById('montant_ht').value
)
||0;



let montantTVA =

parseFloat(
document.getElementById('montant_tva').value
)
||0;




let type =

document.querySelector(
'select[name="type"]'
).value;





let journal =

document.querySelector(
'select[name="journal_type_id"]'
);



let journalText = '';



if(journal.selectedIndex>0){

journalText =
journal.options[journal.selectedIndex].text;

}




let compteSelect =

document.querySelector(
'select[name="liste_des_comptes_id"]'
);



let compteText = '';



if(compteSelect.selectedIndex>0){

compteText =
compteSelect.options[compteSelect.selectedIndex].text;

}





let html='';





if(montantTTC>0){





/*
RECETTE

Débit caisse
Crédit produit
Crédit TVA
*/


if(type=="recette"){


html += `


<tr>

<td>
${journalText}
</td>

<td>
${montantTTC.toFixed(2)}
</td>

<td>
0.00
</td>


</tr>




<tr>

<td>
${compteText}
</td>

<td>
0.00
</td>

<td>
${montantHT.toFixed(2)}
</td>


</tr>




<tr>

<td>
44321 - TVA Facturée
</td>

<td>
0.00
</td>

<td>
${montantTVA.toFixed(2)}
</td>


</tr>


`;


}







/*
DEPENSE

Débit charge
Débit TVA
Crédit caisse
*/


if(type=="depense"){


html += `


<tr>

<td>
${compteText}
</td>

<td>
${montantHT.toFixed(2)}
</td>

<td>
0.00
</td>


</tr>




<tr>

<td>
44511 - TVA récupérable
</td>

<td>
${montantTVA.toFixed(2)}
</td>

<td>
0.00
</td>


</tr>




<tr>

<td>
${journalText}
</td>

<td>
0.00
</td>

<td>
${montantTTC.toFixed(2)}
</td>


</tr>


`;

}



}





document.getElementById('apercu_ecriture').innerHTML =
html;




let debit=0;

let credit=0;




if(type=="recette"){


debit = montantTTC;

credit = montantHT + montantTVA;


}





if(type=="depense"){


debit = montantHT + montantTVA;

credit = montantTTC;


}





document.getElementById('total_debit').innerHTML =
debit.toFixed(2);



document.getElementById('total_credit').innerHTML =
credit.toFixed(2);



}





document
.querySelector(
'select[name="type"]'
)
.addEventListener(
'change',
genererApercu
);




document
.querySelector(
'select[name="journal_type_id"]'
)
.addEventListener(
'change',
genererApercu
);




document
.querySelector(
'select[name="liste_des_comptes_id"]'
)
.addEventListener(
'change',
genererApercu
);



</script>



@endsection