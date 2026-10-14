@extends('layouts.app')
@section('title', 'Transferts internes de trésorerie')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
        <div><h3 class="mb-1"><i class="bi bi-arrow-left-right me-2"></i>Journal des transferts internes de trésorerie</h3><p class="text-muted mb-0">Historique des mouvements entre caisse, banque et Mobile Money.</p></div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-dark text-white"><strong>Nouveau transfert interne</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('journaux.transferts-internes.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-2"><label class="form-label" for="date">Date</label><input id="date" type="date" name="date" class="form-control" value="{{ old('date', today()->toDateString()) }}" required></div>
                <div class="col-md-2"><label class="form-label" for="monnaie">Monnaie</label><select id="monnaie" name="monnaie" class="form-select" required><option value="CDF" @selected(old('monnaie', 'CDF') === 'CDF')>CDF</option><option value="USD" @selected(old('monnaie') === 'USD')>USD</option></select></div>
                <div class="col-md-4"><label class="form-label" for="source_id">Compte source</label><select id="source_id" name="source_id" class="form-select" required><option value="">Choisir</option>@foreach($comptesTresorerie as $compte)<option value="{{ $compte->id }}" data-monnaie="{{ $compte->monnaie }}" @selected((string)old('source_id') === (string)$compte->id)>{{ $compte->code }} — {{ $compte->compte?->designation }} ({{ $compte->monnaie }})</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label" for="destination_id">Compte destinataire</label><select id="destination_id" name="destination_id" class="form-select" required><option value="">Choisir</option>@foreach($comptesTresorerie as $compte)<option value="{{ $compte->id }}" data-monnaie="{{ $compte->monnaie }}" @selected((string)old('destination_id') === (string)$compte->id)>{{ $compte->code }} — {{ $compte->compte?->designation }} ({{ $compte->monnaie }})</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label" for="montant">Montant</label><input id="montant" type="number" name="montant" class="form-control" min="0.01" step="0.01" value="{{ old('montant') }}" required></div>
                <div class="col-md-2"><label class="form-label" for="description">Motif (facultatif)</label><input id="description" name="description" class="form-control" maxlength="255" value="{{ old('description') }}"></div>
                <div class="col-12 text-end"><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer le transfert</button></div>
            </form>
            <small class="text-muted">Le transfert enregistre simultanément une sortie du compte source et une entrée du compte destinataire, dans la même monnaie.</small>
        </div>
    </div>

    <form method="GET" action="{{ route('journaux.transferts-internes') }}" class="card border-0 shadow-sm mb-3"><div class="card-body row g-3 align-items-end">
        <div class="col-md-4"><label class="form-label" for="date_debut">Du</label><input class="form-control" type="date" id="date_debut" name="date_debut" value="{{ request('date_debut') }}"></div>
        <div class="col-md-4"><label class="form-label" for="date_fin">Au</label><input class="form-control" type="date" id="date_fin" name="date_fin" value="{{ request('date_fin') }}"></div>
        <div class="col-md-4 d-flex gap-2"><button class="btn btn-outline-primary">Filtrer</button><a href="{{ route('journaux.transferts-internes') }}" class="btn btn-outline-secondary">Réinitialiser</a></div>
    </div></form>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between"><strong>Historique des transferts</strong><span>{{ $transferts->total() }} transfert(s)</span></div>
        <div class="table-responsive"><table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Date</th><th>Référence</th><th>Compte source</th><th>Compte destinataire</th><th>Motif</th><th class="text-end">Montant</th><th>Monnaie</th><th>Enregistré par</th><th>Bon</th></tr></thead>
            <tbody>
            @forelse($transferts as $transfert)
                @php($entree = $transfert->contrepartieTransfert)
                <tr><td>{{ $transfert->date?->format('d/m/Y') }}</td><td class="fw-semibold">{{ $transfert->reference }}</td><td>{{ $transfert->journalType?->code }} — {{ $transfert->journalType?->compte?->designation }}</td><td>{{ $entree?->journalType?->code ?? '—' }} — {{ $entree?->journalType?->compte?->designation ?? '—' }}</td><td>{{ $transfert->description }}</td><td class="text-end">{{ number_format((float)($transfert->monnaie === 'USD' ? $transfert->sorties_usd : $transfert->sorties_cdf), 2, ',', ' ') }}</td><td><span class="badge bg-secondary">{{ $transfert->monnaie }}</span></td><td>{{ trim(($transfert->user?->prenom ?? '').' '.($transfert->user?->nom ?? '')) ?: '—' }}</td><td class="text-center"><a class="btn btn-sm btn-outline-primary" href="{{ route('journaux.transferts-internes.bon', $transfert->reference) }}"><i class="bi bi-receipt me-1"></i>Bon</a></td></tr>
            @empty<tr><td colspan="9" class="text-center text-muted py-5">Aucun transfert interne enregistré.</td></tr>@endforelse
            </tbody>
        </table></div>
        @if($transferts->hasPages())<div class="card-footer bg-white">{{ $transferts->links() }}</div>@endif
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const monnaie = document.getElementById('monnaie');
    const source = document.getElementById('source_id');
    const destination = document.getElementById('destination_id');
    const syncComptes = () => {
        [source, destination].forEach(select => [...select.options].forEach(option => {
            option.hidden = option.value !== '' && option.dataset.monnaie !== monnaie.value;
            if (option.value === select.value && option.hidden) select.value = '';
        }));
        [...destination.options].forEach(option => {
            if (option.value === source.value && option.value !== '') {
                option.hidden = true;
                if (option.value === destination.value) destination.value = '';
            }
        });
    };
    monnaie.addEventListener('change', syncComptes);
    source.addEventListener('change', syncComptes);
    syncComptes();
});
</script>
@endpush
@endsection
