@extends('layouts.app')
@section('title','Lignes budgétaires')
@section('module-sidebar') @include('budgets._sidebar') @endsection
@section('content') @include('budgets._styles')
<div class="container-fluid py-3 budget-dashboard">
@include('budgets._heading',['eyebrow'=>'Prévisions','heading'=>'Lignes budgétaires','description'=>'Gérez les rubriques, leurs périodes et leurs prévisions initiales.','icon'=>'list-check'])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@can('manageBudgetLines')
<section class="card border-0 shadow-sm mb-4"><div class="card-body p-4"><form method="POST" action="{{ route('parametres.budgets.lignes.store') }}" class="row g-3">@csrf
<div class="col-md-6"><label class="form-label">Département</label><select name="departement_id" class="form-select"><option value="">Tous</option>@foreach($departements as $d)<option value="{{ $d->id }}">{{ $d->designation }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Rubrique budgétaire</label><select name="rubrique_budgetaire_id" class="form-select" required><option value="">Sélectionner</option>@foreach($rubriquesBudgetaires as $r)<option value="{{ $r->id }}" @selected((string)old('rubrique_budgetaire_id')===(string)$r->id)>[{{ $r->nature }}] {{ $r->designation }} — {{ $r->compte?->compte }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Prévision initiale</label><input type="number" min="0" step="0.01" name="prevision_initiale" value="{{ old('prevision_initiale') }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Date de début</label><input type="date" name="date_debut" value="{{ old('date_debut') }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Date de fin</label><input type="date" name="date_fin" value="{{ old('date_fin') }}" class="form-control" required></div>
<div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100">Ajouter la ligne</button></div>
</form></div></section>
@endcan
@include('budgets._report_actions',['rapport'=>'lignes'])
<section class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th>Rubrique</th><th>Période</th><th>Compte</th><th class="text-end">Initial</th><th class="text-end">Révisé</th><th></th></tr></thead>
<tbody>@forelse($lignesBudgetaires as $l)<tr><td><strong>{{ $l->rubrique }}</strong></td><td class="text-nowrap">@if($l->date_debut && $l->date_fin){{ $l->date_debut->format('d/m/Y') }} – {{ $l->date_fin->format('d/m/Y') }}@else<span class="text-muted">Non définie</span>@endif</td><td>{{ $l->compte?->compte }} — {{ $l->compte?->designation }}</td><td class="text-end">{{ number_format($l->prevision_initiale,2,',',' ') }}</td><td class="text-end">{{ number_format($l->budget_revise,2,',',' ') }}</td><td>@can('manageBudgetLines')<div class="d-flex gap-1 justify-content-end"><a href="{{ route('parametres.budgets.lignes.edit',$l) }}" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil-square"></i></a><form method="POST" action="{{ route('parametres.budgets.lignes.mensualiser',$l) }}">@csrf<input type="hidden" name="mode" value="egale"><button class="btn btn-sm btn-outline-info" title="Répartir sur les mois de la période"><i class="bi bi-calendar3"></i></button></form><form method="POST" action="{{ route('parametres.budgets.lignes.destroy',$l) }}" data-confirm="Supprimer cette ligne budgétaire ?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button></form></div>@endcan</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-5">Aucune ligne budgétaire.</td></tr>@endforelse</tbody>
</table></div></section></div>
@endsection
