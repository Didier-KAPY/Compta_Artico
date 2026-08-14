@extends('layouts.app')
@section('title', 'Simulation de clôture')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><span class="text-primary small fw-bold text-uppercase">Simulation sans modification</span><h2 class="mb-0">Clôture du {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</h2></div><a href="{{ route('parametres.clotures.index') }}" class="btn btn-outline-secondary">Retour</a></div>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="row g-3 mb-4">
        @foreach([['Journaux',$journaux->count(),'primary'],['Recettes CDF',$totaux['total_recettes_cdf'],'success'],['Recettes USD',$totaux['total_recettes_usd'],'success'],['Dépenses CDF',$totaux['total_depenses_cdf'],'danger'],['Dépenses USD',$totaux['total_depenses_usd'],'danger'],['Rejetés',$rejetes,'warning']] as [$label,$value,$color])
        <div class="col-sm-6 col-xl-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">{{ $label }}</small><div class="h5 fw-bold text-{{ $color }} mb-0">{{ is_numeric($value) ? number_format($value, 2, ',', ' ') : $value }}</div></div></div></div>
        @endforeach
    </div>
    @forelse($groupes as $cle => $groupe)
        @php([$categorie,$tresorerie,$monnaie]=explode('|',$cle))
        <div class="card border-0 shadow-sm mb-3"><div class="card-header bg-dark text-white d-flex justify-content-between"><strong>{{ strtoupper($categorie) }} — {{ ucfirst(str_replace('_',' ',$tresorerie)) }} — {{ $monnaie }}</strong><span>{{ $groupe->count() }} journal(aux)</span></div><div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>Référence</th><th>Type</th><th>Description</th><th>Statut</th><th class="text-end">Montant TTC</th></tr></thead><tbody>@foreach($groupe as $journal)<tr><td>{{ $journal->reference }}</td><td>{{ ucfirst($journal->type) }}</td><td>{{ $journal->description }}</td><td>{{ $journal->statut }}</td><td class="text-end">{{ number_format($journal->montant_ttc,2,',',' ') }} {{ $journal->monnaie }}</td></tr>@endforeach</tbody></table></div></div>
    @empty<div class="alert alert-warning">Aucun journal non regroupé n’est éligible pour cette date.</div>@endforelse
    @if($journaux->isNotEmpty())
    <div class="card border-danger shadow-sm mt-4"><div class="card-body"><h5 class="fw-bold">Confirmer la clôture</h5><p class="text-muted">Les journaux seront verrouillés et rattachés aux bons quotidiens correspondants.</p>
        <form method="POST" action="{{ route('parametres.clotures.store') }}" class="row g-3">@csrf<input type="hidden" name="date" value="{{ $date }}">
            <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="complementaire" value="1" id="complementaire"><span class="form-check-label">Clôture complémentaire</span></label></div>
            <div class="col-md-8"><label class="form-label">Motif de la complémentaire</label><textarea name="motif" class="form-control" rows="2" placeholder="Obligatoire uniquement pour une clôture complémentaire"></textarea></div>
            <div class="col-md-4 d-flex align-items-end"><button class="btn btn-danger w-100" data-confirm="Confirmer la clôture de cette journée ?"><i class="bi bi-lock me-1"></i>Lancer la clôture</button></div>
        </form>
    </div></div>@endif
</div>
@endsection
