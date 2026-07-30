@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <h2>États financiers SYSCOHADA</h2>
    <p class="text-muted">Période du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }} — Devise de présentation : Franc congolais (CDF)</p>
    <div class="card shadow-sm border-0 mb-4"><div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6"><label class="form-label">Date de début</label><input type="date" name="date_debut" value="{{ $dateDebut }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Date de fin</label><input type="date" name="date_fin" value="{{ $dateFin }}" class="form-control" required></div>
            <div class="col-12 d-flex gap-2"><button formaction="{{ route('comptabilite.etats-financiers.bilan') }}" class="btn btn-primary">Afficher le bilan</button><button formaction="{{ route('comptabilite.etats-financiers.compte-resultat') }}" class="btn btn-success">Afficher le compte de résultat</button></div>
        </form>
    </div></div>
    <div class="row g-4">
        <div class="col-md-6"><a class="card h-100 shadow-sm border-0 text-decoration-none text-dark" href="{{ route('comptabilite.etats-financiers.bilan', ['date_debut'=>$dateDebut,'date_fin'=>$dateFin]) }}"><div class="card-body py-4"><h4>Bilan final</h4><p class="text-muted mb-0">Actif, passif, résultat net et contrôle d’équilibre.</p></div></a></div>
        <div class="col-md-6"><a class="card h-100 shadow-sm border-0 text-decoration-none text-dark" href="{{ route('comptabilite.etats-financiers.compte-resultat', ['date_debut'=>$dateDebut,'date_fin'=>$dateFin]) }}"><div class="card-body py-4"><h4>Compte de résultat</h4><p class="text-muted mb-0">Produits, charges et résultat net de l’exercice.</p></div></a></div>
    </div>
</div>
@endsection
