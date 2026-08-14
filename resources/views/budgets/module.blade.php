@extends('layouts.app')
@section('title', $pageTitle)
@section('module-sidebar') @include('budgets._sidebar') @endsection
@section('content')
@include('budgets._styles')
<div class="container-fluid py-3 budget-dashboard">@include('budgets._heading',['eyebrow'=>'Gestion budgétaire','heading'=>$pageTitle,'description'=>$pageDescription,'icon'=>$pageIcon])<section class="card border-0 shadow-sm"><div class="card-body"><div class="empty-module-state"><i class="bi bi-{{ $pageIcon }}"></i><strong>Fonctionnalité en préparation</strong><span>{{ $pageDescription }} Cette page est prête à recevoir les données métier lors de la prochaine étape.</span></div></div></section></div>
@endsection
