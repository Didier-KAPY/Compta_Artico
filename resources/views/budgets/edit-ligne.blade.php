@extends('layouts.app')
@section('title','Modifier une ligne budgétaire')
@section('module-sidebar') @include('budgets._sidebar') @endsection
@section('content') @include('budgets._styles')
<div class="container-fluid py-3 budget-dashboard">@include('budgets._heading',['eyebrow'=>'Lignes budgétaires','heading'=>'Modifier la ligne budgétaire','description'=>'Modifiez la rubrique, la période, le compte ou la prévision.','icon'=>'pencil-square'])
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<section class="card border-0 shadow-sm"><div class="card-body p-4"><form method="POST" action="{{ route('parametres.budgets.lignes.update',$ligne) }}" class="row g-3">@csrf @method('PUT')
<div class="col-md-6"><label class="form-label">Département</label><select name="departement_id" class="form-select"><option value="">Tous</option>@foreach($departements as $d)<option value="{{ $d->id }}" @selected((string)old('departement_id',$ligne->departement_id)===(string)$d->id)>{{ $d->designation }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Rubrique budgétaire</label><select name="rubrique_budgetaire_id" class="form-select" required>@foreach($rubriquesBudgetaires as $r)<option value="{{ $r->id }}" @selected((string)old('rubrique_budgetaire_id',$ligne->rubrique_budgetaire_id)===(string)$r->id)>[{{ $r->nature }}] {{ $r->designation }} — {{ $r->compte?->compte }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Prévision initiale</label><input type="number" min="0" step="0.01" name="prevision_initiale" value="{{ old('prevision_initiale',$ligne->prevision_initiale) }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Date de début</label><input type="date" name="date_debut" value="{{ old('date_debut',$ligne->date_debut?->toDateString()) }}" class="form-control" required></div><div class="col-md-6"><label class="form-label">Date de fin</label><input type="date" name="date_fin" value="{{ old('date_fin',$ligne->date_fin?->toDateString()) }}" class="form-control" required></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control">{{ old('description',$ligne->description) }}</textarea></div><div class="col-12 d-flex justify-content-end gap-2"><a href="{{ route('parametres.budgets.lignes') }}" class="btn btn-light border">Annuler</a><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer</button></div>
</form></div></section></div>@endsection
