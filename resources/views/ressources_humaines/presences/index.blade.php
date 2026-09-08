@extends('layouts.app')
@section('title','Présences')
@section('module-sidebar') @include('ressources_humaines._sidebar') @endsection
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div><h2 class="fw-bold mb-1">Présences</h2><p class="text-muted mb-0">Suivi des arrivées, départs, retards et modes de pointage.</p></div>
        <div class="d-flex gap-2">@can('manageAttendance')<button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#saisie-manuelle"><i class="bi bi-pencil-square me-1"></i>Saisie manuelle</button><a href="{{route('parametres.rh.presences.scanner')}}" class="btn btn-success"><i class="bi bi-qr-code-scan me-1"></i>Scanner QR Code</a>@endcan</div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{$errors->first()}}</div>@endif

    <div class="row g-3 mb-4">
        @foreach([['Présences',$statsPresence['presences'],'success','person-check'],['Absences',$statsPresence['absences'],'danger','person-x'],['Retards',$statsPresence['retards'],'warning','clock-history'],['Heures supplémentaires',number_format($statsPresence['heures_supplementaires'],2,',',' ').' h','info','hourglass-split']] as [$label,$value,$color,$icone])
            <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3"><span class="rounded-3 bg-{{$color}} bg-opacity-10 text-{{$color}} p-3"><i class="bi bi-{{$icone}} fs-4"></i></span><div><small class="text-muted">{{$label}}</small><div class="fs-3 fw-bold text-{{$color}}">{{$value}}</div></div></div></div></div>
        @endforeach
    </div>

    @can('manageAttendance')<div class="collapse mb-4" id="saisie-manuelle"><div class="card border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Enregistrer manuellement une présence</div><div class="card-body"><form method="POST" action="{{route('parametres.rh.presences.store')}}" class="row g-3">@csrf
        <div class="col-lg-4"><label class="form-label">Employé *</label><select name="employe_id" class="form-select" required><option value="">Sélectionner</option>@foreach($employes as $employe)<option value="{{$employe->id}}">{{$employe->matricule}} — {{$employe->nom}} {{$employe->prenom}}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Date *</label><input type="date" name="date" value="{{now()->toDateString()}}" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Arrivée</label><input type="time" name="heure_arrivee" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Départ</label><input type="time" name="heure_depart" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Statut *</label><select name="statut" class="form-select" required>@foreach(['Présent','Absent','Retard','Mission','Télétravail','Congé','Jour férié'] as $statut)<option>{{$statut}}</option>@endforeach</select></div>
        <div class="col-md-5"><label class="form-label">Observation / justification</label><input name="observation" class="form-control"></div>
        <div class="col-12 text-end"><button class="btn btn-primary">Enregistrer la présence</button></div>
    </form></div></div></div>@endcan

    @include('ressources_humaines._table_presences')
    <div class="mt-3">{{$elements->links()}}</div>
</div>
@endsection
