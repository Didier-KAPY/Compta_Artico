@extends('layouts.app')
@section('title', 'Imports comptables')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between mb-4"><div><span class="text-primary small fw-bold text-uppercase">Outils</span><h2>Import du plan comptable</h2></div><a href="{{ route('parametres.parametre') }}" class="btn btn-outline-secondary">Retour</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card border-0 shadow-sm mb-4"><div class="card-body"><p class="text-muted">CSV limité à 1 000 lignes. Colonnes obligatoires : <code>compte;designation;nature</code>. Colonne facultative : <code>observation</code>.</p><form method="POST" enctype="multipart/form-data" action="{{ route('parametres.imports.preview') }}" class="d-flex gap-2">@csrf<input type="file" name="fichier" accept=".csv,.txt" class="form-control" required><button class="btn btn-primary text-nowrap" data-loading-text="Analyse...">Prévisualiser</button></form></div></div>
    @if($apercu)
    <div class="card border-0 shadow-sm"><div class="card-header d-flex justify-content-between"><strong>Prévisualisation</strong><form method="POST" action="{{ route('parametres.imports.store') }}" data-confirm="Confirmer l’import des nouveaux comptes ?">@csrf<button class="btn btn-success btn-sm" data-loading-text="Import...">Confirmer l’import</button></form></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Compte</th><th>Désignation</th><th>Nature</th><th>Contrôle</th></tr></thead><tbody>@foreach($apercu as $ligne)<tr><td>{{ $ligne['compte'] }}</td><td>{{ $ligne['designation'] }}</td><td>{{ $ligne['nature'] }}</td><td><span class="badge bg-{{ $ligne['existe'] ? 'warning' : 'success' }}">{{ $ligne['existe'] ? 'Doublon ignoré' : 'Nouveau' }}</span></td></tr>@endforeach</tbody></table></div></div>
    @endif
</div>
@endsection
