@extends('layouts.app')


@section('content')


<div class="container py-4">


<div class="d-flex justify-content-between mb-4">


<h4>

<i class="bi bi-pencil-square"></i>

Modifier compte

</h4>


<a href="{{ route('parametres.comptes') }}"
class="btn btn-secondary btn-sm">

Retour

</a>


</div>





<div class="card shadow-sm">


<div class="card-header bg-dark text-white">

Modification du compte

</div>




<div class="card-body">



<form action="{{ route('parametres.comptes.update',$compte->id) }}"
method="POST">


@csrf
@method('PUT')



<div class="row">



<div class="col-md-6 mb-3">

<label class="fw-bold">
Compte
</label>

<input type="text"
name="compte"
class="form-control"
value="{{ $compte->compte }}"
required>

</div>





<div class="col-md-6 mb-3">

<label class="fw-bold">
Désignation
</label>

<input type="text"
name="designation"
class="form-control"
value="{{ $compte->designation }}"
required>

</div>





<div class="col-md-6 mb-3">

<label class="fw-bold">
Nature
</label>


<select name="nature"
class="form-select"
required>


<option {{ $compte->nature=='Actif'?'selected':'' }}>
Actif
</option>


<option {{ $compte->nature=='Passif'?'selected':'' }}>
Passif
</option>


<option {{ $compte->nature=='Charge'?'selected':'' }}>
Charge
</option>


<option {{ $compte->nature=='Produit'?'selected':'' }}>
Produit
</option>


</select>

</div>






<div class="col-md-12 mb-3">

<label class="fw-bold">
Observation
</label>


<textarea name="observation"
class="form-control">{{ $compte->observation }}</textarea>


</div>



</div>





<div class="text-end">

<button class="btn btn-success">

<i class="bi bi-save"></i>

Enregistrer

</button>

</div>



</form>



</div>

</div>


</div>


@endsection