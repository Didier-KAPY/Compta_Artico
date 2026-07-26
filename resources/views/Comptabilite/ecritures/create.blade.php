@extends('layouts.app')

@section('content')

<div class="container-fluid mt-3">

<div class="card shadow-sm border-0">

<div class="card-header bg-dark text-white py-2">
    <h6 class="mb-0">
        <i class="bi bi-journal-text"></i>
        Nouvelle écriture comptable
    </h6>
</div>


<div class="card-body p-3">


@if(session('success'))

<div class="alert alert-success py-2">
    {{ session('success') }}
</div>

@endif


<form method="POST" action="{{ route('ecritures.store') }}">

@csrf


{{-- INFORMATIONS GENERALES --}}

<div class="row g-2 mb-3">


<div class="col-md-2">

<label class="small fw-bold">
Date *
</label>

<input type="date"
name="date"
class="form-control form-control-sm"
value="{{ date('Y-m-d') }}"
required>

</div>



<div class="col-md-4">

<label class="small fw-bold">
Journal *
</label>


<select name="journal_type_id"
class="form-select form-select-sm"
required>


<option value="">
Choisir
</option>


@foreach($journaux as $journal)

<option value="{{ $journal->id }}">

{{ $journal->compte->compte ?? '' }}
-
{{ $journal->compte->designation ?? '' }}

@if($journal->est_tresorerie)
(TRESORERIE)
@endif

</option>

@endforeach


</select>


</div>




<div class="col-md-2">

<label class="small fw-bold">
Sens *
</label>


<select name="sens"
class="form-select form-select-sm"
required>

<option value="debit">
Débit
</option>

<option value="credit">
Crédit
</option>

</select>


</div>




<div class="col-md-4">

<label class="small fw-bold">
Description
</label>


<input type="text"
name="description"
class="form-control form-control-sm">

</div>


</div>



{{-- IMPUTATION --}}


<div class="d-flex justify-content-between mb-2">

<h6>
<i class="bi bi-calculator"></i>
Imputation comptable
</h6>


<button type="button"
id="ajouterLigne"
class="btn btn-primary btn-sm">

<i class="bi bi-plus"></i>
Ajouter ligne

</button>


</div>




<table class="table table-sm table-bordered"
id="tableLignes">


<thead class="table-light">

<tr>

<th width="35%">
Compte
</th>

<th width="30%">
Libellé
</th>

<th width="20%">
Montant
</th>

<th width="5%">
Action
</th>

</tr>

</thead>



<tbody>


<tr>


<td>


<select name="lignes[0][compte_id]"
class="form-select form-select-sm"
required>


<option value="">
Choisir compte
</option>



@foreach($comptes as $compte)

<option value="{{ $compte->id }}">

{{ $compte->compte }}
-
{{ $compte->designation }}

</option>


@endforeach


</select>


</td>



<td>


<input type="text"
name="lignes[0][libelle]"
class="form-control form-control-sm"
required>


</td>



<td>


<input type="number"
name="lignes[0][montant]"
class="form-control form-control-sm montant"
step="0.01"
value="0"
required>


</td>



<td class="text-center">


<button type="button"
class="btn btn-danger btn-sm supprimer">

<i class="bi bi-trash"></i>

</button>


</td>



</tr>


</tbody>



<tfoot class="table-light">


<tr>

<th colspan="2">
TOTAL
</th>


<th id="totalMontant">
0.00
</th>


<th></th>


</tr>


</tfoot>


</table>




<div class="text-end">


<button type="submit"
class="btn btn-success btn-sm">


<i class="bi bi-check-circle"></i>

Enregistrer


</button>


</div>


</form>


</div>

</div>

</div>


@endsection




@section('scripts')


<script>


let index = 1;



// Ajouter une ligne

document.getElementById('ajouterLigne')
.addEventListener('click', function(){


let ligne = `

<tr>

<td>

<select name="lignes[${index}][compte_id]"
class="form-select form-select-sm"
required>


<option value="">
Choisir compte
</option>


@foreach($comptes as $compte)

<option value="{{ $compte->id }}">

{{ $compte->compte }}
-
{{ $compte->designation }}

</option>

@endforeach


</select>

</td>


<td>


<input type="text"
name="lignes[${index}][libelle]"
class="form-control form-control-sm"
required>


</td>


<td>


<input type="number"
name="lignes[${index}][montant]"
class="form-control form-control-sm montant"
step="0.01"
value="0">


</td>


<td class="text-center">


<button type="button"
class="btn btn-danger btn-sm supprimer">

<i class="bi bi-trash"></i>

</button>


</td>


</tr>


`;


document.querySelector('#tableLignes tbody')
.insertAdjacentHTML(
'beforeend',
ligne
);


index++;


});





// Supprimer ligne

document.addEventListener('click', function(e){


if(e.target.closest('.supprimer')){


e.target.closest('tr').remove();

calculerTotal();


}


});





// Calcul total

document.addEventListener('input', function(e){


if(e.target.classList.contains('montant')){

calculerTotal();

}


});





function calculerTotal(){


let total = 0;


document.querySelectorAll('.montant')
.forEach(function(m){

total += Number(m.value) || 0;

});


document.getElementById('totalMontant')
.innerHTML = total.toFixed(2);


}



</script>


@endsection