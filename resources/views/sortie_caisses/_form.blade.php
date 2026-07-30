@php $sortie = $sortie ?? null; @endphp
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Date *</label>
        <input type="date" name="date" class="form-control" value="{{ old('date', $sortie?->date ?? date('Y-m-d')) }}" required>
    </div>
    <div class="col-md-8">
        <label class="form-label">Bénéficiaire *</label>
        <input type="text" name="beneficiaire" class="form-control" value="{{ old('beneficiaire', $sortie?->beneficiaire) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Montant *</label>
        <input type="number" name="montant" step="0.01" min="0" class="form-control" value="{{ old('montant', $sortie?->montant) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Monnaie *</label>
        <select name="monnaie" class="form-select" required>
            <option value="CDF" {{ old('monnaie', $sortie?->monnaie ?? 'CDF') === 'CDF' ? 'selected' : '' }}>CDF</option>
            <option value="USD" {{ old('monnaie', $sortie?->monnaie) === 'USD' ? 'selected' : '' }}>USD</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Type de paiement *</label>
        <select name="type" class="form-select" required>
            @foreach(['Caisse', 'Banque', 'Mobile Money'] as $type)
                <option value="{{ $type }}" {{ old('type', $sortie?->type ?? 'Caisse') === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Motif *</label>
        <input type="text" name="motif" class="form-control" value="{{ old('motif', $sortie?->motif) }}" required>
    </div>
    <div class="col-12">
        <label class="form-label">Observation *</label>
        <textarea name="observation" rows="4" class="form-control" required>{{ old('observation', $sortie?->observation) }}</textarea>
    </div>
</div>
