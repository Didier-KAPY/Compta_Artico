@extends('layouts.app')


@section('title','Génération BRC')


@section('content')


<div class="container">


<div class="card shadow-sm mt-4">


<div class="card-header bg-dark text-white">

<i class="bi bi-file-earmark-text"></i>

<strong>
Générer un Bon de Régularisation Comptable
</strong>


</div>



<div class="card-body">


<form method="GET"
action="{{ route('ecritures.brc.generer') }}">



<div class="row">


<div class="col-md-5">


<label class="form-label fw-bold">

Date début

</label>


<input type="date"
name="date_debut"
class="form-control"
value="{{ now()->toDateString() }}"
required>


</div>



<div class="col-md-5">


<label class="form-label fw-bold">

Date fin

</label>


<input type="date"
name="date_fin"
class="form-control"
value="{{ now()->toDateString() }}"
required>


</div>



<div class="col-md-2 d-flex align-items-end">


<button class="btn btn-primary w-100">

<i class="bi bi-search"></i>

Afficher

</button>


</div>



</div>



</form>


</div>


</div>


</div>


@endsection