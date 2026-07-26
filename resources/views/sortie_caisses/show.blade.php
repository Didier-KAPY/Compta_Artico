@extends('layouts.app')

@section('content')

@php

$role = strtolower(auth()->user()->role->designation ?? '');

$rolesGestion = [
    'super admin',
    'admin',
    'directeur général',
    'gérant'
];

$isGestion = in_array($role,$rolesGestion);

$isSuperAdmin = $role === 'super admin';

@endphp


<div class="container py-4">


{{-- ======================================================
HEADER
====================================================== --}}


<div class="d-flex justify-content-between align-items-center mb-3">


<div>

<h4 class="mb-0">

<i class="bi bi-cash-stack me-2"></i>

Bon de Sortie

</h4>


<small class="text-muted">

Dossier financier / validation caisse

</small>


</div>



<div class="d-flex gap-2">


<a href="{{ route('sortie-caisses.index') }}"
class="btn btn-secondary btn-sm">

<i class="bi bi-arrow-left"></i>

Retour

</a>



<a href="#"
class="btn btn-outline-dark btn-sm">

<i class="bi bi-printer"></i>

Imprimer

</a>


</div>


</div>




{{-- ======================================================
MESSAGES
====================================================== --}}


@if(session('success'))

<div class="alert alert-success alert-dismissible fade show">

<i class="bi bi-check-circle me-2"></i>

{{ session('success') }}


<button type="button"
class="btn-close"
data-bs-dismiss="alert"></button>


</div>

@endif



@if(session('error'))

<div class="alert alert-danger alert-dismissible fade show">

<i class="bi bi-exclamation-triangle me-2"></i>

{{ session('error') }}


<button type="button"
class="btn-close"
data-bs-dismiss="alert"></button>


</div>

@endif




{{-- ======================================================
STATUT
====================================================== --}}



<div class="alert

@if($sortie->statut == 'Validé')

alert-success

@elseif($sortie->statut == 'Rejeté')

alert-danger

@else

alert-warning

@endif

d-flex justify-content-between align-items-center">


<div>

<strong>

Statut :

</strong>


{{ $sortie->statut ?? 'En attente' }}


</div>




<div>

<strong>

Numéro :

</strong>


{{ $sortie->numero }}


</div>


</div>






{{-- ======================================================
INFORMATIONS GENERALES
====================================================== --}}



<div class="card shadow-sm mb-3">


<div class="card-header bg-dark text-white">


<i class="bi bi-info-circle me-2"></i>

Informations générales


</div>



<div class="card-body">



<div class="row g-3">



<div class="col-md-3">

<strong>Date</strong>

<br>

{{ \Carbon\Carbon::parse($sortie->date)->format('d/m/Y') }}

</div>





<div class="col-md-3">

<strong>Bénéficiaire</strong>

<br>

{{ $sortie->beneficiaire }}

</div>





@if($isSuperAdmin)

<div class="col-md-3">


<strong>

Utilisateur créateur

</strong>


<br>


{{ $sortie->user?->prenom ?? '' }}

{{ $sortie->user?->nom ?? '' }}


</div>


@endif





<div class="col-md-3">


<strong>

Monnaie

</strong>


<br>


<span class="badge bg-secondary">

{{ $sortie->monnaie }}

</span>


</div>



</div>





<hr>




<div class="row">



<div class="col-md-8">


<strong>

Motif

</strong>


<br>


{{ $sortie->motif }}



</div>




<div class="col-md-4 text-end">


<strong>

Montant

</strong>


<h4 class="text-primary">


{{ number_format($sortie->montant,2,',',' ') }}


</h4>


</div>



</div>




</div>


</div>
{{-- ======================================================
DETAILS ETAT DE BESOIN
====================================================== --}}


@if($sortie->etatBesoin && $sortie->etatBesoin->lignes->count())


<div class="card shadow-sm mb-3">


<div class="card-header bg-dark text-white">

<i class="bi bi-list-ul me-2"></i>

Détails de l'état de besoin

</div>



<div class="card-body">


<div class="table-responsive">


<table class="table table-bordered table-hover">


<thead class="table-light">


<tr>

<th>#</th>

<th>Désignation</th>

<th>Quantité</th>

<th>Prix unitaire</th>

<th>Montant</th>

</tr>


</thead>



<tbody>


@foreach($sortie->etatBesoin->lignes as $ligne)


<tr>


<td>

{{ $loop->iteration }}

</td>



<td>

{{ $ligne->designation }}

</td>



<td>

{{ $ligne->quantite }}

</td>



<td>

{{ number_format($ligne->prix_unitaire,2,',',' ') }}

</td>



<td>

{{ number_format($ligne->montant,2,',',' ') }}

</td>



</tr>



@endforeach


</tbody>



<tfoot>


<tr>


<th colspan="4" class="text-end">

Total

</th>



<th>

{{ number_format($sortie->etatBesoin->lignes->sum('montant'),2,',',' ') }}

</th>



</tr>


</tfoot>



</table>


</div>


</div>


</div>


@endif







{{-- ======================================================
OBSERVATION
====================================================== --}}



<div class="card shadow-sm mb-3">


<div class="card-header bg-dark text-white">


<i class="bi bi-chat-left-text me-2"></i>


Observation


</div>



<div class="card-body">


{{ $sortie->observation ?? 'Aucune observation' }}


</div>


</div>








{{-- ======================================================
ETAT DE BESOIN ASSOCIE
====================================================== --}}



<div class="card shadow-sm mb-3">


<div class="card-header bg-dark text-white">


<i class="bi bi-file-earmark-text me-2"></i>


État de besoin associé


</div>




<div class="card-body d-flex justify-content-between align-items-center">



<div>


<strong>

État de besoin lié :

</strong>


</div>





<div>



@if($sortie->etat_besoin_id)



<a href="{{ route('etat-besoins.show',$sortie->etat_besoin_id) }}"

class="btn btn-outline-primary btn-sm">


<i class="bi bi-eye"></i>


Voir l'état de besoin


</a>




@else


<span class="text-muted">

Aucun état de besoin associé

</span>


@endif



</div>



</div>


</div>
{{-- ======================================================
ZONE TRAITEMENT
UNIQUEMENT SUPER ADMIN / ADMIN / DG / GERANT
====================================================== --}}


@if($isGestion)


<div class="card shadow-lg border-0 mt-4">


<div class="card-header bg-dark text-white">


<i class="bi bi-shield-check me-2"></i>


Traitement du bon de sortie


</div>



<div class="card-body">



@if($sortie->statut == 'En attente')



<div class="alert alert-warning">


<i class="bi bi-exclamation-triangle-fill me-2"></i>


Ce bon est en attente de traitement.


</div>





<div class="d-flex justify-content-end gap-2">





<a href="{{ route('sortie-caisses.edit',$sortie->id) }}"

class="btn btn-warning">


<i class="bi bi-pencil-square me-1"></i>


Modifier


</a>







<button type="button"

class="btn btn-primary"

data-bs-toggle="modal"

data-bs-target="#modalTraitement">


<i class="bi bi-check-circle me-1"></i>


Traiter le bon


</button>





</div>





@elseif(in_array($sortie->statut,['Validé','Rejeté']))



<div class="alert alert-info">


<i class="bi bi-info-circle me-2"></i>


Ce bon a déjà été traité.


</div>




<div class="d-flex justify-content-end">



<form method="POST"

action="{{ route('sortie-caisses.attente',$sortie->id) }}">


@csrf



<button type="submit"

class="btn btn-secondary">


<i class="bi bi-arrow-counterclockwise me-1"></i>


Remettre en attente


</button>



</form>



</div>



@endif



</div>


</div>



@endif







{{-- ======================================================
MODAL TRAITEMENT
VALIDATION / REJET DIRECT
====================================================== --}}



@if($isGestion && $sortie->statut == 'En attente')



<div class="modal fade"

id="modalTraitement"

tabindex="-1">



<div class="modal-dialog modal-dialog-centered">



<div class="modal-content">





<form method="POST"

action="{{ route('sortie-caisses.valider',$sortie->id) }}">



@csrf





<div class="modal-header bg-dark text-white">


<h5 class="modal-title">


<i class="bi bi-cash-stack me-2"></i>


Traitement du bon de sortie


</h5>



<button type="button"

class="btn-close btn-close-white"

data-bs-dismiss="modal">

</button>


</div>






<div class="modal-body">





<div class="alert alert-info">



<strong>

Numéro :

</strong>


{{ $sortie->numero }}


<br>




<strong>

Montant :

</strong>


{{ number_format($sortie->montant,2,',',' ') }}

{{ $sortie->monnaie }}


<br>




<strong>

Bénéficiaire :

</strong>


{{ $sortie->beneficiaire }}



</div>







<div class="mb-3">


<label class="form-label fw-bold">


Observation


</label>



<textarea name="observation"

class="form-control"

rows="3"

placeholder="Observation de traitement...">{{ old('observation',$sortie->observation) }}</textarea>



</div>






</div>








<div class="modal-footer">






<button type="button"

class="btn btn-secondary"

data-bs-dismiss="modal">


Annuler


</button>







{{-- REJET DIRECT --}}



<button type="submit"

formaction="{{ route('sortie-caisses.rejeter',$sortie->id) }}"

class="btn btn-danger">


<i class="bi bi-x-circle me-1"></i>


Rejeter


</button>







{{-- VALIDATION DIRECTE --}}



<button type="submit"

class="btn btn-success">


<i class="bi bi-check-circle me-1"></i>


Valider


</button>





</div>







</form>





</div>


</div>


</div>




@endif
{{-- ======================================================
COMPTABLE / DAF
CONSULTATION UNIQUEMENT
====================================================== --}}


@if(in_array($role,['comptable','daf']))


<div class="alert alert-info mt-4">


<i class="bi bi-eye me-2"></i>


<strong>Consultation uniquement :</strong>


Vous pouvez consulter ce bon de sortie mais vous ne pouvez pas
le modifier, le valider, le rejeter ou le remettre en attente.


</div>


@endif







{{-- ======================================================
AFFICHAGE APRES TRAITEMENT
POUR ROLES DE GESTION
====================================================== --}}



@if($isGestion && $sortie->statut != 'En attente')



<div class="card shadow-sm border-0 mt-4">



<div class="card-body text-center">





@if($sortie->statut == 'Validé')



<i class="bi bi-check-circle-fill text-success"

style="font-size:70px;"></i>



<h5 class="mt-3 text-success">

Bon de sortie validé

</h5>




@elseif($sortie->statut == 'Rejeté')



<i class="bi bi-x-circle-fill text-danger"

style="font-size:70px;"></i>



<h5 class="mt-3 text-danger">

Bon de sortie rejeté

</h5>



@endif







<p class="text-muted">


Statut actuel :


<strong>

{{ $sortie->statut }}

</strong>



</p>







<a href="{{ route('sortie-caisses.index') }}"

class="btn btn-secondary">


<i class="bi bi-arrow-left"></i>


Retour


</a>





</div>


</div>




@endif







</div>


@endsection