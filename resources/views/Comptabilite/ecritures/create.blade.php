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

<div class="alert alert-light border py-2 d-flex align-items-center gap-2">
    <i class="bi bi-currency-exchange text-primary"></i>
    @if($taux)
        <span>1 USD = <strong>{{ number_format($taux->taux_de_change, 2, ',', ' ') }} CDF</strong></span>
    @else
        <span class="text-warning">Aucun taux de change configuré pour les imputations en USD.</span>
    @endif
</div>


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
id="journal_type_id_search"
class="form-select form-select-sm journal-search"
required>


<option value="">
Choisir
</option>


@foreach($journaux as $journal)

<option value="{{ $journal->id }}" {{ (string) old('journal_type_id') === (string) $journal->id ? 'selected' : '' }}>

{{ $journal->compte->compte }} - {{ $journal->compte->designation }}

@if($journal->est_tresorerie)
(TRESORERIE)
@endif

</option>

@endforeach


</select>
@error('journal_type_id')<div class="text-danger small">{{ $message }}</div>@enderror

</div>

<div class="col-md-2">
<label class="small fw-bold">Monnaie *</label>
<select name="monnaie" id="monnaie" class="form-select form-select-sm @error('monnaie') is-invalid @enderror" required>
<option value="CDF" {{ old('monnaie', 'CDF') === 'CDF' ? 'selected' : '' }}>CDF</option>
<option value="USD" {{ old('monnaie') === 'USD' ? 'selected' : '' }}>USD</option>
</select>
@error('monnaie')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="col-md-2">
<label class="small fw-bold">Sens du compte lié au journal *</label>
<select name="sens" class="form-select form-select-sm" required>
<option value="debit">Débit</option>
<option value="credit">Crédit</option>
</select>
<small class="text-muted">Les comptes d’imputation prendront automatiquement le sens opposé.</small>
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
class="form-select form-select-sm compte-search"
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
0.00 {{ old('monnaie', 'CDF') }}
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

function activerRecherche(selecteur, placeholder) {
    $(selecteur).each(function () {
        if (!$(this).hasClass('select2-hidden-accessible')) {
            $(this).select2({
                width: '100%',
                placeholder: placeholder,
                allowClear: true,
                language: {
                    noResults: function () { return 'Aucun résultat trouvé'; },
                    searching: function () { return 'Recherche…'; }
                }
            });
        }
    });
}

activerRecherche('.journal-search', 'Rechercher un journal');
activerRecherche('.compte-search', 'Rechercher un compte');

const monnaieSelect = document.getElementById('monnaie');

function actualiserMonnaie() {
    calculerTotal();
}

monnaieSelect.addEventListener('change', actualiserMonnaie);
actualiserMonnaie();



// Ajouter une ligne

document.getElementById('ajouterLigne')
.addEventListener('click', function(){


let ligne = `

<tr>

<td>

<select name="lignes[${index}][compte_id]"
class="form-select form-select-sm compte-search"
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

activerRecherche('#tableLignes tbody tr:last-child .compte-search', 'Rechercher un compte');


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

document.getElementById('totalMontant').textContent = total.toFixed(2) + ' ' + monnaieSelect.value;


}



</script>


@endsection
