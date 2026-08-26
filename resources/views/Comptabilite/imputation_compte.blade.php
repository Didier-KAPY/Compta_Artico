@extends('layouts.app')

@section('title', 'Journal des opérations diverses')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Journal des opérations diverses</h4>
            <small class="text-muted">Journaux validés en attente d’imputation dans les comptes</small>
        </div>
        <a href="{{ route('ecritures.liste') }}" class="btn btn-outline-secondary">
            <i class="bi bi-journal-check me-1"></i>Écritures déjà validées
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @forelse($journaux as $journal)
        @php
            $reference = mb_strtoupper(trim((string) $journal->reference));
            $pieceObligatoire = preg_match('/^(BSC|BSB|BSM)/', $reference) === 1;
            $ecrituresEnAttente = $journal->ecritures->where('statut', 'En attente');
        @endphp
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <div><strong>{{ $journal->reference }}</strong> — {{ $journal->description }}</div>
                <span class="badge bg-success">Journal validé</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-2"><small class="text-muted d-block">Date</small><strong>{{ $journal->date?->format('d/m/Y') }}</strong></div>
                    <div class="col-md-2"><small class="text-muted d-block">Monnaie</small><strong>{{ $journal->monnaie }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">Type de journal</small><strong>{{ $journal->journalType?->libelle ?? '—' }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">Validé par</small><strong>{{ trim(($journal->validateur?->prenom ?? '').' '.($journal->validateur?->nom ?? '')) ?: '—' }}</strong></div>
                    <div class="col-md-2 text-md-end"><a href="{{ route('journaux.show', $journal) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Voir le journal</a></div>
                </div>

                <form method="POST" action="{{ route('comptabilite.imputation-compte.traiter', $journal) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light"><tr><th>Pièce</th><th>Libellé</th><th>Débit CDF</th><th>Crédit CDF</th><th style="min-width:300px">Compte d’imputation</th></tr></thead>
                            <tbody>
                            @foreach($ecrituresEnAttente as $ecriture)
                                <tr>
                                    <td>{{ $ecriture->piece ?: $journal->reference }}</td>
                                    <td>{{ $ecriture->libelle }}</td>
                                    <td class="text-end">{{ number_format($ecriture->debit_cdf, 2, ',', ' ') }}</td>
                                    <td class="text-end">{{ number_format($ecriture->credit_cdf, 2, ',', ' ') }}</td>
                                    <td>
                                        <select name="comptes[{{ $ecriture->id }}]" class="form-select compte-imputation" required>
                                            <option value="">Choisir un compte</option>
                                            @foreach($comptes as $compte)
                                                <option value="{{ $compte->id }}" @selected((string) old('comptes.'.$ecriture->id, $ecriture->liste_des_comptes_id) === (string) $compte->id)>
                                                    {{ $compte->compte }} — {{ $compte->designation }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot class="table-light"><tr><th colspan="2" class="text-end">Totaux</th><th class="text-end">{{ number_format($ecrituresEnAttente->sum('debit_cdf'), 2, ',', ' ') }}</th><th class="text-end">{{ number_format($ecrituresEnAttente->sum('credit_cdf'), 2, ',', ' ') }}</th><th></th></tr></tfoot>
                        </table>
                    </div>

                    <div class="row align-items-end g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold">Pièce justificative @if($pieceObligatoire)<span class="text-danger">*</span>@endif</label>
                            <input type="file" name="piece_justificative" class="form-control" accept=".pdf,.jpg,.jpeg,.png" @required($pieceObligatoire && blank($journal->piece_justificatif))>
                            <small class="text-muted">{{ $pieceObligatoire ? 'Obligatoire pour les références BSC, BSB et BSM.' : 'Facultative pour cette référence.' }}</small>
                        </div>
                        <div class="col-md-5 text-md-end">
                            @if(auth()->user()?->hasRole(['Super Admin', 'Comptable']))
                                <button class="btn btn-success"><i class="bi bi-check2-all me-1"></i>Traiter le journal</button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm"><div class="card-body text-center py-5 text-muted"><i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>Aucun journal validé n’attend une imputation.</div></div>
    @endforelse

    {{ $journaux->links() }}
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.compte-imputation').select2({ width: '100%', placeholder: 'Rechercher un compte' });
});
</script>
@endpush
