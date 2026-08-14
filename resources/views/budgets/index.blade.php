@extends('layouts.app')
@section('title','Tableau de bord budgétaire')
@section('module-sidebar') @include('budgets._sidebar') @endsection
@section('content') @include('budgets._styles')
<div class="container-fluid py-3 budget-dashboard">@include('budgets._heading',['eyebrow'=>'Pilotage financier','heading'=>'Tableau de bord budgétaire','description'=>'Synthèse séparée des recettes et des dépenses à partir de la comptabilité validée.','icon'=>'pie-chart-fill'])
@php
$recettesKpis=[['Prévisions',$syntheseBudgetaire['recettes']['prevision'],'wallet2'],['Réalisées',$syntheseBudgetaire['recettes']['realise'],'graph-up-arrow'],['Reste à réaliser',$syntheseBudgetaire['recettes']['reste'],'hourglass-split'],['Taux',$syntheseBudgetaire['recettes']['taux'],'percent']];
$depensesKpis=[['Initial',$syntheseBudgetaire['depenses']['initial']],['Révisé',$syntheseBudgetaire['depenses']['revise']],['Engagé',$syntheseBudgetaire['depenses']['engage']],['Réalisé',$syntheseBudgetaire['depenses']['realise']],['Disponible',$syntheseBudgetaire['depenses']['disponible']],['Exécution',$syntheseBudgetaire['depenses']['taux']],['Mobilisation',$syntheseBudgetaire['depenses']['mobilisation']]];
$soldesKpis=[['Solde prévisionnel',$syntheseBudgetaire['solde_previsionnel']],['Solde réalisé',$syntheseBudgetaire['solde_realise']],['Écart du solde',$syntheseBudgetaire['ecart_solde']]];
@endphp
<h5 class="text-success mb-3">Recettes</h5><div class="row g-3 mb-4">@foreach($recettesKpis as $kpi)<div class="col-sm-6 col-xl-3"><div class="budget-kpi"><span class="budget-kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-{{ $kpi[2] }}"></i></span><div><small>{{ $kpi[0] }}</small><strong>{{ number_format($kpi[1],$kpi[0]==='Taux'?1:2,',',' ') }} {{ $kpi[0]==='Taux'?'%':'CDF' }}</strong></div></div></div>@endforeach</div>
<h5 class="text-danger mb-3">Dépenses</h5><div class="row g-3 mb-4">@foreach($depensesKpis as $kpi)<div class="col-sm-6 col-xl-3"><div class="budget-kpi"><span class="budget-kpi-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-graph-down-arrow"></i></span><div><small>{{ $kpi[0] }}</small><strong>{{ number_format($kpi[1],in_array($kpi[0],['Exécution','Mobilisation'])?1:2,',',' ') }} {{ in_array($kpi[0],['Exécution','Mobilisation'])?'%':'CDF' }}</strong></div></div></div>@endforeach</div>
<h5 class="mb-3">Synthèse</h5><div class="row g-3">@foreach($soldesKpis as $kpi)<div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body p-4"><small class="text-muted">{{ $kpi[0] }}</small><div class="fs-3 fw-bold text-{{ $kpi[1]>=0?'success':'danger' }}">{{ number_format($kpi[1],2,',',' ') }} CDF</div></div></div></div>@endforeach</div></div>
@endsection
