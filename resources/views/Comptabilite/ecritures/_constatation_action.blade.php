@php
    $constatationLiee = $ecriture->constatation ?? $ecriture->journal?->constatation;
    $peutConstater = auth()->user()?->hasRole(['Super Admin', 'Comptable']);
@endphp
@if($constatationLiee)
    <span class="badge bg-info text-dark">Constatée</span>
    <a href="{{ route('ecritures.constatation', $constatationLiee->source_ecriture_id) }}" class="btn btn-sm btn-outline-primary">Voir la constatation</a>
    <a href="{{ route('ecritures.show', $constatationLiee->source_ecriture_id) }}#historique-constatation" class="btn btn-sm btn-outline-secondary">Historique</a>
@elseif($peutConstater && app(\App\Services\ConstatationComptableService::class)->eligible($ecriture))
    <a href="{{ route('ecritures.constatation', $ecriture) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-journal-plus me-1"></i>Constater</a>
@endif
