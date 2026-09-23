@forelse(app(\App\Services\PiecesJustificativesService::class)->liste($document) as $pieceJointe)
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <span><i class="bi bi-file-earmark-check text-success me-1"></i>{{ $pieceJointe['nom'] }}</span>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route($pieceRoute, ['id' => $document->id, 'piece' => hash('sha256', $pieceJointe['path'])]) }}">Consulter</a>
            <a class="btn btn-sm btn-outline-secondary" data-no-loading href="{{ route($pieceRoute, ['id' => $document->id, 'piece' => hash('sha256', $pieceJointe['path']), 'telecharger' => 1]) }}">Télécharger</a>
            @if(($canDeletePiece ?? false) && isset($pieceDeleteRoute))
                <form method="POST" action="{{ route($pieceDeleteRoute, ['id' => $document->id, 'piece' => hash('sha256', $pieceJointe['path'])]) }}" data-confirm="Supprimer cette pièce justificative ?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Supprimer</button>
                </form>
            @endif
        </div>
    </div>
@empty
    <div class="alert alert-warning mb-0">Aucune pièce justificative.</div>
@endforelse
