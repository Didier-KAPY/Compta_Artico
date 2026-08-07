@extends('layouts.app')


@section('content')
@php $isSuperAdmin = auth()->user()?->hasRole('Super Admin') ?? false; @endphp


<div class="container-fluid py-4">


<div class="card shadow-sm border-0 mb-4">


<div class="card-body d-flex justify-content-between align-items-center">


<div>

<h4>

<i class="bi bi-gear text-primary"></i>

Paramétrage comptable

</h4>


<p class="text-muted mb-0">

Configuration des comptes automatiques

</p>


</div>


</div>

</div>





@if(session('success'))

<div class="alert alert-success">

{{session('success')}}

</div>

@endif








<div class="card shadow-sm border-0 mb-4">


<div class="card-header bg-primary text-white">

Ajouter un paramètre

</div>



<div class="card-body">


<form method="POST"
action="{{route('parametres.comptables.store')}}">


@csrf


<div class="row g-3">



<div class="col-md-4">

<label class="form-label">

Code

</label>


<input type="text"
name="code"
class="form-control"
placeholder="TVA_COLLECTEE">


</div>





<div class="col-md-4">


<label class="form-label">

Désignation

</label>


<input type="text"
name="designation"
class="form-control"
placeholder="TVA facturée">


</div>







<div class="col-md-4">


<label class="form-label">

Compte

</label>


<select name="liste_des_comptes_id"
class="form-select">


<option value="">

Sélectionner

</option>


@foreach($comptes as $compte)


<option value="{{$compte->id}}">


{{$compte->compte}}
-
{{$compte->designation}}


</option>


@endforeach


</select>


</div>



</div>


<button class="btn btn-primary mt-3">

<i class="bi bi-save"></i>

Enregistrer

</button>


</form>


</div>


</div>










<div class="card shadow-sm border-0">


<div class="card-header bg-dark text-white">

Liste des paramètres

</div>



<div class="table-responsive">


<table class="table table-hover mb-0">


<thead class="table-light">

<tr>

<th>Code</th>

<th>Désignation</th>

<th>Compte</th>

@if($isSuperAdmin)
<th>Utilisateur</th>
@endif

<th width="120">
Action
</th>


</tr>

</thead>


<tbody>


@forelse($parametrages as $param)


<tr>


<td>

{{$param->code}}

</td>

<td>

{{$param->designation}}

</td>


<td>

@if($param->compte)

{{$param->compte->compte}}
-
{{$param->compte->designation}}

@endif


</td>

@if($isSuperAdmin)
<td>{{ trim(($param->user?->prenom ?? '').' '.($param->user?->nom ?? '')) ?: 'Système' }}</td>
@endif



<td>


<form method="POST"
action="{{route('parametres.comptables.destroy',$param->id)}}">


@csrf

@method('DELETE')


<button class="btn btn-sm btn-danger"
onclick="return confirm('Supprimer ce paramètre ?')">


<i class="bi bi-trash"></i>


</button>


</form>


</td>


</tr>


@empty


<tr>

<td colspan="{{ $isSuperAdmin ? 5 : 4 }}"
class="text-center text-muted">

Aucun paramètre configuré

</td>

</tr>


@endforelse


</tbody>

<tfoot><tr><td colspan="{{ $isSuperAdmin ? 5 : 4 }}">{{ $parametrages->withQueryString()->links() }}</td></tr></tfoot>


</table>


</div>


</div>




</div>


@endsection
