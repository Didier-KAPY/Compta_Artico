@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-pencil me-2"></i>Modifier {{ $brc->reference }}</strong>
            <a href="{{ route('brc.show', $brc) }}" class="btn btn-outline-light btn-sm">Retour</a>
        </div>
        <div class="card-body">
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="alert alert-warning"><strong>Montants verrouillés :</strong> aucun montant ni le total du BRC ne peut être modifié.</div>
            <form method="POST" action="{{ route('brc.update', $brc) }}">@csrf @method('PATCH')
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><label class="form-label fw-bold">Date *</label><input type="date" name="date" value="{{ old('date', $brc->date?->toDateString()) }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label fw-bold">Journal *</label><select name="journal_type_id" class="form-select" required>@foreach($journaux as $journal)<option value="{{ $journal->id }}" @selected((string)old('journal_type_id', $brc->journal_type_id) === (string)$journal->id)>{{ $journal->code }} — {{ $journal->libelle }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label fw-bold">Monnaie *</label><select name="monnaie" class="form-select"><option value="CDF" @selected(old('monnaie', $brc->monnaie)==='CDF')>CDF</option><option value="USD" @selected(old('monnaie', $brc->monnaie)==='USD')>USD</option></select></div>
                    <div class="col-md-3"><label class="form-label fw-bold">Sens *</label><select name="sens" class="form-select"><option value="debit" @selected(old('sens', $brc->sens)==='debit')>Débit</option><option value="credit" @selected(old('sens', $brc->sens)==='credit')>Crédit</option></select></div>
                    <div class="col-md-3"><label class="form-label fw-bold">Mode de paiement *</label><select name="mode_paiement" class="form-select"><option value="mobile_money" @selected(old('mode_paiement', $brc->mode_paiement ?? 'mobile_money')==='mobile_money')>Mobile money</option><option value="espèces" @selected(old('mode_paiement', $brc->mode_paiement)==='espèces')>Espèces</option><option value="banque" @selected(old('mode_paiement', $brc->mode_paiement)==='banque')>Banque</option></select></div>
                </div>
                <div class="table-responsive"><table class="table table-bordered align-middle"><thead class="table-light"><tr><th>Compte</th><th>Libellé</th><th>Montant verrouillé</th></tr></thead><tbody>
                    @foreach($brc->lignes as $index => $ligne)<tr>
                        <td><input type="hidden" name="lignes[{{ $index }}][id]" value="{{ $ligne->id }}"><select name="lignes[{{ $index }}][compte_id]" class="form-select" required>@foreach($comptes as $compte)<option value="{{ $compte->id }}" @selected((string)old("lignes.$index.compte_id", $ligne->liste_des_comptes_id)===(string)$compte->id)>{{ $compte->compte }} — {{ $compte->designation }}</option>@endforeach</select></td>
                        <td><input name="lignes[{{ $index }}][libelle]" value="{{ old("lignes.$index.libelle", $ligne->libelle) }}" class="form-control" required></td>
                        <td><input value="{{ number_format($ligne->montant, 2, ',', ' ') }} {{ $brc->monnaie }}" class="form-control bg-light fw-bold" disabled></td>
                    </tr>@endforeach
                </tbody><tfoot><tr><th colspan="2">TOTAL NON MODIFIABLE</th><th>{{ number_format($brc->total, 2, ',', ' ') }} {{ $brc->monnaie }}</th></tr></tfoot></table></div>
                <div class="text-end"><button class="btn btn-success"><i class="bi bi-save me-1"></i>Enregistrer les modifications</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
