@extends('layouts.app')
@section('title', 'Modifier un budget')
@section('module-sidebar') @include('budgets._sidebar') @endsection
@section('content')
@include('budgets._styles')
<div class="container-fluid py-3 budget-dashboard">
@include('budgets._heading',['eyebrow'=>'Budgets annuels','heading'=>'Modifier le budget','description'=>'Actualisez le périmètre, la période ou la prévision du budget sélectionné.','icon'=>'pencil-square'])
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<section class="card border-0 shadow-sm"><div class="card-body p-4"><form method="POST" action="{{ route('parametres.budgets.update',$budget) }}" class="row g-3">@csrf @method('PUT')
<div class="col-md-6"><label class="form-label">Département</label><select name="departement_id" class="form-select"><option value="">Tous</option>@foreach($departements as $d)<option value="{{ $d->id }}" @selected((string)old('departement_id',$budget->departement_id)===(string)$d->id)>{{ $d->designation }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Compte</label><select name="liste_des_comptes_id" class="form-select" required>@foreach($comptes as $c)<option value="{{ $c->id }}" @selected((string)old('liste_des_comptes_id',$budget->liste_des_comptes_id)===(string)$c->id)>{{ $c->compte }} — {{ $c->designation }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Date de début</label><input type="date" name="date_debut" class="form-control" value="{{ old('date_debut',$budget->date_debut->toDateString()) }}" required></div><div class="col-md-4"><label class="form-label">Date de fin</label><input type="date" name="date_fin" class="form-control" value="{{ old('date_fin',$budget->date_fin->toDateString()) }}" required></div><div class="col-md-4"><label class="form-label">Prévision CDF</label><input type="number" step="0.01" min="0" name="montant_prevu" class="form-control" value="{{ old('montant_prevu',$budget->montant_prevu) }}" required><input type="hidden" name="monnaie" value="CDF"></div>
<div class="col-12 d-flex justify-content-end gap-2"><a href="{{ route('parametres.budgets.annuels') }}" class="btn btn-light border">Annuler</a><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer les modifications</button></div>
</form></div></section></div>
@endsection
