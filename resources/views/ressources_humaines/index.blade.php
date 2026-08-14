@extends('layouts.app')
@section('title','Ressources humaines')
@section('module-sidebar') @include('ressources_humaines._sidebar') @endsection
@section('content')
<div class="container-fluid py-4"><div class="p-4 rounded-4 text-white shadow-sm mb-4" style="background:linear-gradient(135deg,#0f2744,#167c80)"><span class="badge bg-white text-info mb-2">Ressources humaines</span><h2 class="fw-bold">Tableau de bord RH</h2><p class="mb-0 text-white-50">Vue générale des effectifs et de l’organisation.</p></div>
<div class="row g-3">@foreach([['Employés',$nombreEmployes,'people','primary'],['Employés actifs',$employesActifs,'person-check','success'],['Départements',$nombreDepartements,'diagram-3','info'],['Fonctions',$nombreFonctions,'briefcase','warning']] as [$libelle,$valeur,$icone,$couleur])<div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4 d-flex align-items-center gap-3"><span class="rounded-3 bg-{{ $couleur }} bg-opacity-10 text-{{ $couleur }} p-3"><i class="bi bi-{{ $icone }} fs-4"></i></span><div><small class="text-muted">{{ $libelle }}</small><div class="fs-3 fw-bold">{{ $valeur }}</div></div></div></div></div>@endforeach</div>
<div class="row g-3 mt-1"><div class="col-md-6"><div class="alert alert-warning mb-0"><i class="bi bi-calendar-event me-2"></i><strong>{{ $congesAttente }}</strong> demande(s) de congé en attente.</div></div><div class="col-md-6"><div class="alert alert-danger mb-0"><i class="bi bi-file-earmark-excel me-2"></i><strong>{{ $contratsExpiration }}</strong> contrat(s) arrivent à échéance dans 30 jours.</div></div></div>
</div>
@endsection
