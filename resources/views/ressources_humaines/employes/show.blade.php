@extends('layouts.app')
@section('title',$employe->matricule)
@section('module-sidebar') @include('ressources_humaines._sidebar') @endsection
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3"><div><h2 class="fw-bold mb-0">{{$employe->nom}} {{$employe->postnom}} {{$employe->prenom}}</h2><span class="text-muted">{{$employe->matricule}}</span></div><div><a class="btn btn-outline-danger" href="{{route('parametres.rh.employes.pdf',$employe)}}">PDF</a>@can('updateEmployees') <a class="btn btn-primary" href="{{route('parametres.rh.employes.edit',$employe)}}">Modifier</a>@endcan</div></div>
    <div class="row g-3">
        <div class="col-lg-7"><div class="card card-body border-0 shadow-sm"><h5>Informations professionnelles</h5><dl class="row mb-0"><dt class="col-sm-4">Statut</dt><dd class="col-sm-8">{{$employe->statut}}</dd><dt class="col-sm-4">Département</dt><dd class="col-sm-8">{{$employe->departement?->designation??'—'}}</dd><dt class="col-sm-4">Fonction</dt><dd class="col-sm-8">{{$employe->fonction?->designation??'—'}}</dd><dt class="col-sm-4">Date d’embauche</dt><dd class="col-sm-8">{{$employe->date_embauche?->format('d/m/Y')??'—'}}</dd></dl></div></div>
        <div class="col-lg-5"><div class="card card-body border-0 shadow-sm"><h5>Historique</h5><p class="mb-1">{{$employe->contrats->count()}} contrat(s)</p><p class="mb-1">{{$employe->conges->count()}} congé(s)</p><p class="mb-0">{{$employe->evaluations->count()}} évaluation(s)</p></div></div>
        @can('manageAttendance')<div class="col-12"><div class="card card-body border-0 shadow-sm"><h5><i class="bi bi-qr-code me-2"></i>QR Code de pointage</h5><p class="text-muted">Code individuel réservé à l’enregistrement de l’arrivée et du départ.</p>@if($employe->qr_token)<a href="{{route('parametres.rh.employes.qr.show',$employe)}}" class="btn btn-outline-primary">Afficher / imprimer le QR Code</a>@elseif($employe->statut==='Actif')<form method="POST" action="{{route('parametres.rh.employes.qr.generate',$employe)}}">@csrf<button class="btn btn-primary">Générer le QR Code</button></form>@else<div class="alert alert-warning mb-0">Le QR Code ne peut être généré que pour un employé actif.</div>@endif</div></div>@endcan
    </div>
</div>
@endsection
