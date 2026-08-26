@extends('layouts.app')

@section('content')

<div class="container py-4">


@php

$role = strtolower(auth()->user()->role?->designation ?? '');

$isSuperAdmin = $role == 'super admin';
$showActions = ! auth()->user()->isAccounting();
$canCreateSortie = auth()->user()->isSuperAdmin() || auth()->user()->isManagement() || in_array($role, ['caissier', 'caissière', 'trésorier', 'trésorière'], true);
$canManageSortie = auth()->user()->isSuperAdmin() || auth()->user()->isManagement();


@endphp



<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3">

    <h4 class="mb-0">
        <i class="bi bi-cash-coin me-2"></i>
        Sorties de Caisse
    </h4>


    <div class="d-flex gap-2 flex-wrap">
    @include('partials.period-export-buttons', ['rapport' => 'sorties'])
    @if($canCreateSortie)
    <a href="{{ route('sortie-caisses.create') }}"
       class="btn btn-primary">

        <i class="bi bi-plus-circle me-1"></i>

        Nouvelle sortie

    </a>
    @endif
    </div>

</div>





<!-- FILTRES -->

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="card shadow-sm border-0 mb-3">

<div class="card-body">


<form method="GET"
      action="{{ route('sortie-caisses.index') }}">


<div class="row g-3">



<div class="col-md-4">

<label class="form-label">

Recherche par numéro

</label>


<input type="text"
       name="numero"
       class="form-control"
       value="{{ request('numero') }}"
       placeholder="Ex : BSC-2026-001">


</div>





<div class="col-md-3">

<label class="form-label">

Date début

</label>


<input type="date"
       name="date_debut"
       class="form-control"
       value="{{ request('date_debut') }}">


</div>





<div class="col-md-3">

<label class="form-label">

Date fin

</label>


<input type="date"
       name="date_fin"
       class="form-control"
       value="{{ request('date_fin') }}">


</div>





<div class="col-md-2 d-flex align-items-end">


<button type="submit"
        class="btn btn-primary w-100">


<i class="bi bi-search"></i>

Filtrer


</button>


</div>



</div>




<div class="mt-3">


<a href="{{ route('sortie-caisses.index') }}"
   class="btn btn-secondary">


<i class="bi bi-arrow-clockwise"></i>

Réinitialiser


</a>


</div>



</form>


</div>

</div>






<!-- TABLE -->

<div class="card shadow-sm border-0">


<div class="card-body p-0">


<div class="table-responsive">



<table class="table table-hover align-middle mb-0">



<thead class="table-dark">


<tr>


<th>N°</th>


@if($isSuperAdmin)
<th>Validé par</th>
@endif



<th>Date</th>

<th>Bénéficiaire</th>

<th>Motif</th>

<th class="text-end">
Montant
</th>


<th>
Monnaie
</th>


<th>
Statut
</th>


@if($showActions)
<th class="text-center">
Actions
</th>
@endif



</tr>


</thead>





<tbody>



@forelse($sorties as $sortie)
@php
    $lignesAffichees = $sortie->appliquer_tva && (float) $sortie->montant_tva > 0
        ? collect([
            ['libelle' => $sortie->motif.' (HT)', 'montant' => (float) $sortie->montant_ht, 'nature' => 'HT'],
            ['libelle' => 'TVA '.number_format((float) $sortie->taux_tva, 2, ',', ' ').' %', 'montant' => (float) $sortie->montant_tva, 'nature' => 'TVA'],
        ])
        : collect([['libelle' => $sortie->motif, 'montant' => (float) $sortie->montant, 'nature' => null]]);
@endphp
@foreach($lignesAffichees as $index => $ligne)
<tr class="{{ $ligne['nature'] === 'TVA' ? 'table-info' : '' }}">
<td><strong>{{ $sortie->numero }}</strong>@if($ligne['nature'])<br><span class="badge bg-{{ $ligne['nature'] === 'TVA' ? 'info text-dark' : 'primary' }}">{{ $ligne['nature'] }}</span>@endif</td>
@if($isSuperAdmin)
<td>{{ trim(($sortie->validateur?->prenom ?? '').' '.($sortie->validateur?->nom ?? '')) ?: 'Non validé' }}</td>
@endif
<td>{{ \Carbon\Carbon::parse($sortie->date)->format('d/m/Y') }}</td>
<td>{{ $sortie->beneficiaire }}</td>
<td>{{ $ligne['libelle'] }}</td>
<td class="text-end fw-bold">{{ number_format($ligne['montant'], 2, ',', ' ') }}</td>
<td><span class="badge bg-secondary">{{ $sortie->monnaie }}</span></td>
<td>
@if($sortie->statut == 'Validé')<span class="badge bg-success">Validé</span>
@elseif($sortie->statut == 'Rejeté')<span class="badge bg-danger">Rejeté</span>
@else<span class="badge bg-warning text-dark">En attente</span>@endif
</td>
@if($showActions)
<td class="text-center">
@if($index === 0)
<div class="dropdown"><button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">Actions</button><ul class="dropdown-menu">
<li><a class="dropdown-item" href="{{ route('sortie-caisses.show',$sortie->id) }}">👁 Voir</a></li>
<li><a class="dropdown-item" href="{{ route('sortie-caisses.imprimer', $sortie->id) }}" target="_blank"><i class="bi bi-printer me-2"></i>Imprimer</a></li>
<li><a class="dropdown-item" href="{{ route('sortie-caisses.pdf', $sortie->id) }}"><i class="bi bi-file-earmark-pdf me-2"></i>Télécharger PDF</a></li>
@if($canManageSortie && $sortie->statut !== 'Validé')<li><a class="dropdown-item" href="{{ route('sortie-caisses.edit', $sortie->id) }}"><i class="bi bi-pencil-square me-2"></i>Modifier</a></li>@endif
</ul></div>
@else
<span class="text-muted">Même bon</span>
@endif
</td>
@endif
</tr>
@endforeach
@empty



<tr>


<td colspan="{{ $isSuperAdmin ? 9 : ($showActions ? 8 : 7) }}"
    class="text-center text-muted py-4">


Aucune sortie de caisse trouvée


</td>


</tr>



@endforelse



</tbody>


</table>


</div>


</div>





<div class="card-footer bg-white">


<div class="d-flex justify-content-center">


{{ $sorties->links() }}


</div>


</div>



</div>


</div>


@endsection
