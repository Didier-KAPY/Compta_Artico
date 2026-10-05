<form method="GET" action="{{ route('dashboard') }}" class="d-flex flex-wrap align-items-end gap-3 mb-4">
    <div>
        <label for="dashboardMonth" class="form-label">Mois à consulter</label>
        <input type="month" id="dashboardMonth" name="mois" value="{{ $selectedMonth }}" class="form-control" required>
        @error('mois')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary">Afficher</button>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Mois en cours</a>
    <span class="text-muted pb-2">{{ $periodDescription ?? 'Période affichée' }} : {{ $monthLabel }}</span>
</form>
