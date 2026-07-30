@extends('layouts.app')


@section('title','Génération BRC')


@section('content')


<div class="container-fluid py-3">


<div class="card shadow-sm mt-4">


<div class="card-header bg-dark text-white d-flex justify-content-between">

<i class="bi bi-file-earmark-text"></i>

<strong>
Générer un Bon de Régularisation Comptable
</strong>

<small>Écritures validées · Montants CDF</small>


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
value="{{ old('date_debut', now()->startOfMonth()->toDateString()) }}"
required>

@error('date_debut')<div class="text-danger small">{{ $message }}</div>@enderror


</div>



<div class="col-md-5">


<label class="form-label fw-bold">

Date fin

</label>


<input type="date"
name="date_fin"
class="form-control"
value="{{ old('date_fin', now()->toDateString()) }}"
required>

@error('date_fin')<div class="text-danger small">{{ $message }}</div>@enderror


</div>



<div class="col-md-4 mt-3">
<label class="form-label fw-bold">Journal</label>
<select name="journal_type_id" class="form-select">
<option value="">Tous les journaux</option>
@foreach($journalTypes as $journal)
<option value="{{ $journal->id }}" {{ old('journal_type_id') == $journal->id ? 'selected' : '' }}>{{ $journal->code }} — {{ $journal->libelle }}</option>
@endforeach
</select>
</div>

<div class="col-md-2 d-flex align-items-end mt-3">


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
