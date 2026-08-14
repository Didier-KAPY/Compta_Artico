@extends('layouts.app')
@section('title','Validation des lignes budgétaires')
@section('module-sidebar') @include('budgets._sidebar') @endsection
@section('content')
@include('budgets._styles')
<div class="container-fluid py-3 budget-dashboard">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@include('budgets._report_actions',['rapport'=>'lignes'])
<section class="card border-0 shadow-sm"><div class="card-header bg-white border-0 p-4"><h5 class="mb-1">Lignes à valider et à suivre</h5><small class="text-muted">Les lignes créées apparaissent automatiquement ici.</small></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Rubrique</th><th>Nature</th><th>Budget</th><th>Période</th><th class="text-end">Prévision</th><th class="text-end">Disponible / Reste</th><th>Statut</th><th class="text-end">Action</th></tr></thead><tbody>@forelse($lignesBudgetaires as $ligne)<tr><td class="fw-semibold">{{ $ligne->rubrique }}</td><td><span class="badge bg-{{ $ligne->nature_budgetaire==='RECETTE'?'success':'danger' }}">{{ $ligne->nature_budgetaire }}</span></td><td>{{ $ligne->budget?->libelle }}</td><td>@if($ligne->date_debut && $ligne->date_fin){{ $ligne->date_debut->format('d/m/Y') }} – {{ $ligne->date_fin->format('d/m/Y') }}@else—@endif</td><td class="text-end">{{ number_format($ligne->prevision_initiale,2,',',' ') }}</td><td class="text-end">{{ number_format($ligne->nature_budgetaire==='DEPENSE'?$ligne->disponible_comptable:$ligne->reste_a_realiser,2,',',' ') }}</td><td><span class="badge bg-{{ $ligne->statut==='Active'?'success':'warning' }}">{{ $ligne->statut }}</span></td><td class="text-end">@if($ligne->statut==='En attente') @can('validateBudget')<form method="POST" action="{{ route('parametres.budgets.lignes.valider',$ligne) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success"><i class="bi bi-check-lg me-1"></i>Valider</button></form>@endcan @else<span class="text-success"><i class="bi bi-check-circle me-1"></i>Validée</span>@endif</td></tr>@empty<tr><td colspan="8" class="text-center text-muted py-5">Aucune ligne budgétaire.</td></tr>@endforelse</tbody></table></div></section>
</div>
@endsection
