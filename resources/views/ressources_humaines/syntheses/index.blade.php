@extends('layouts.app')
@section('title','Synthèses mensuelles')
@section('module-sidebar') @include('ressources_humaines._sidebar') @endsection
@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold">Synthèses mensuelles</h2>
    <p class="text-muted">Consolidation des pointages enregistrés, à valider avant le calcul de la paie.</p>
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    @if(session('warning'))<div class="alert alert-warning">{{session('warning')}}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{$errors->first()}}</div>@endif
    @can('manageAttendance')
    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h5 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Génération collective</h5><p class="text-muted mb-0">Générer les synthèses de tous les employés actifs en un seul clic.</p></div>
        <form method="POST" action="{{route('parametres.rh.syntheses.store-all')}}" class="d-flex flex-wrap align-items-end gap-2" data-confirm="Générer les synthèses de tous les employés actifs pour cette période ?">@csrf
            <div><label class="form-label">Année</label><input name="annee" type="number" value="{{now()->year}}" min="2000" max="2100" class="form-control" required></div>
            <div><label class="form-label">Mois</label><input name="mois" type="number" min="1" max="12" value="{{now()->month}}" class="form-control" required></div>
            <button class="btn btn-primary"><i class="bi bi-calendar-check me-1"></i>Générer toutes les synthèses</button>
        </form></div>
    </div></div>
    <div class="card border-0 shadow-sm mb-4"><div class="card-body"><form method="POST" action="{{route('parametres.rh.syntheses.store')}}" class="row g-3">@csrf
        <div class="col-md-5"><label class="form-label">Employé</label><select name="employe_id" class="form-select" required><option value="">Sélectionner</option>@foreach($employes as $e)<option value="{{$e->id}}">{{$e->matricule}} — {{$e->nom}} {{$e->prenom}}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Année</label><input name="annee" type="number" value="{{now()->year}}" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Mois</label><input name="mois" type="number" min="1" max="12" value="{{now()->month}}" class="form-control" required></div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary">Générer la synthèse</button></div>
    </form></div></div>
    @endcan
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Période</th><th>Employé</th><th>Présents</th><th>Congés</th><th>Missions</th><th>Abs. non rémunérées</th><th>Retard</th><th>H. suppl.</th><th>Statut</th><th></th></tr></thead>
        <tbody>@forelse($syntheses as $s)<tr>
            <td>{{str_pad($s->mois,2,'0',STR_PAD_LEFT)}}/{{$s->annee}}</td>
            <td>{{$s->employe?->nom}} {{$s->employe?->prenom}}<small class="d-block text-muted">{{$s->employe?->matricule}}</small></td>
            <td>{{$s->jours_presents}} / {{$s->jours_ouvrables_prevus}}</td><td>{{$s->jours_conges_payes}}</td><td>{{$s->jours_mission}}</td><td>{{$s->jours_absence_non_remuneree}}</td><td>{{$s->retard_total_minutes}} min</td><td>{{$s->heures_supplementaires_validees}} h</td><td>{{$s->statut}}</td>
            <td><div class="d-flex gap-1">
                @can('manageAttendance')
                    @if($s->statut==='Générée')<form method="POST" action="{{route('parametres.rh.syntheses.transition',$s)}}">@csrf @method('PATCH')<input type="hidden" name="statut" value="À vérifier"><button class="btn btn-sm btn-outline-primary">À vérifier</button></form>
                    @elseif($s->statut==='À vérifier')<form method="POST" action="{{route('parametres.rh.syntheses.transition',$s)}}">@csrf @method('PATCH')<input type="hidden" name="statut" value="Validée"><button class="btn btn-sm btn-success">Valider</button></form>@endif
                @endcan
                @if(auth()->user()->isSuperAdmin())
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modifier-synthese-{{$s->id}}" title="Modifier"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#supprimer-synthese-{{$s->id}}" title="Supprimer"><i class="bi bi-trash"></i></button>
                @endif
            </div></td>
        </tr>@empty<tr><td colspan="10" class="text-center py-5 text-muted">Aucune synthèse.</td></tr>@endforelse</tbody>
    </table></div></div><div class="mt-3">{{$syntheses->links()}}</div>
</div>
@if(auth()->user()->isSuperAdmin())
@foreach($syntheses as $s)
<div class="modal fade" id="modifier-synthese-{{$s->id}}" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{route('parametres.rh.syntheses.update',$s)}}" class="modal-content">@csrf @method('PUT')
    <div class="modal-header"><h5 class="modal-title">Modifier la synthèse — {{$s->employe?->matricule}}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        @foreach(['jours_ouvrables_prevus'=>'Jours ouvrables','jours_presents'=>'Présences','jours_conges_payes'=>'Congés payés','jours_mission'=>'Missions','jours_absence_non_remuneree'=>'Absences non rémunérées','nombre_retards'=>'Nombre de retards','retard_total_minutes'=>'Retard total (minutes)','heures_supplementaires_validees'=>'Heures supplémentaires'] as $champ=>$libelle)
        <div class="col-md-4"><label class="form-label">{{$libelle}}</label><input type="number" name="{{$champ}}" value="{{$s->$champ}}" min="0" @if(in_array($champ,['jours_conges_payes','jours_mission','jours_absence_non_remuneree','heures_supplementaires_validees'])) step="0.01" @endif class="form-control" required></div>
        @endforeach
        <div class="col-12"><label class="form-label">Motif de la modification</label><textarea name="motif_modification" class="form-control" minlength="5" required></textarea></div>
    </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary">Enregistrer</button></div>
</form></div></div>
<div class="modal fade" id="supprimer-synthese-{{$s->id}}" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{route('parametres.rh.syntheses.destroy',$s)}}" class="modal-content">@csrf @method('DELETE')
    <div class="modal-header"><h5 class="modal-title">Supprimer la synthèse</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><p>Confirmez la suppression de la synthèse {{$s->mois}}/{{$s->annee}} de {{$s->employe?->nom}} {{$s->employe?->prenom}}.</p><label class="form-label">Motif obligatoire</label><textarea name="motif_suppression" class="form-control" minlength="5" required></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button><button class="btn btn-danger">Supprimer</button></div>
</form></div></div>
@endforeach
@endif
@endsection
