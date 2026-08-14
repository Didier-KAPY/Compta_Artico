@extends('layouts.app')
@section('title','États budgétaires')
@section('module-sidebar') @include('budgets._sidebar') @endsection
@section('content') @include('budgets._styles')
<div class="container-fluid py-3 budget-dashboard">@include('budgets._heading',['eyebrow'=>'Rapports','heading'=>'États budgétaires','description'=>'Imprimez ou téléchargez les situations budgétaires disponibles.','icon'=>'file-earmark-bar-graph'])
<div class="row g-3">@foreach([['budgets','Budgets annuels','calendar2-range'],['lignes','Budget par ligne et compte','list-check'],['execution','Budget / Réalisé et écarts','bar-chart-line']] as [$rapport,$titre,$icon])<div class="col-md-4"><section class="card border-0 shadow-sm h-100"><div class="card-body p-4"><i class="bi bi-{{ $icon }} fs-2 text-primary"></i><h5 class="mt-3">{{ $titre }}</h5><p class="text-muted small">État consolidé disponible en impression, PDF et Excel.</p>@include('budgets._report_actions',['rapport'=>$rapport])</div></section></div>@endforeach</div></div>@endsection
