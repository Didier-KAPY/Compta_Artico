@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="journal-form mx-auto">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="page-kicker">Comptabilité</span>
                <h3 class="fw-bold mb-1"><i class="bi bi-journal-plus text-primary me-2"></i>Nouveau journal {{ match($natureJournal ?? null) {'caisse' => 'Caisse', 'banque' => 'Banque', 'mobile_money' => 'Mobile Money', default => 'de trésorerie'} }}</h3>
                <p class="text-muted mb-0">Saisie automatique en partie double</p>
            </div>
            <a href="{{ route('journaux.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-2"></i>Veuillez corriger les erreurs suivantes :</div>
                <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('journaux.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($natureJournal ?? null)<input type="hidden" name="journal_nature" value="{{ $natureJournal }}">@endif
            <div class="card journal-main-card">
                <div class="card-header journal-card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <h5 class="mb-1"><i class="bi bi-pencil-square me-2"></i>Informations de l’opération</h5>
                        <small>Les champs marqués d’un astérisque sont obligatoires.</small>
                    </div>
                    <div class="rate-chip">
                        <i class="bi bi-currency-exchange"></i>
                        @if($taux)
                            <span>1 USD = <strong>{{ number_format($taux->taux_de_change, 2, ',', ' ') }} CDF</strong></span>
                        @else
                            <span class="text-warning">Aucun taux défini</span>
                        @endif
                    </div>
                </div>

                <div class="card-body p-0">
                    <section class="form-section">
                        <div class="section-title"><span>1</span><div><h6>Journal et opération</h6><small>Nature et date du mouvement</small></div></div>
                        @php $appliquerTva = (string) old('appliquer_tva', '0'); @endphp
                        <div class="row g-3">
                            @if($natureJournal ?? null)
                                <input type="hidden" name="journal_type_id" id="journal_type_id" value="{{ old('journal_type_id', $journalTypes->firstWhere('monnaie', old('monnaie', 'CDF'))?->id) }}">
                            @else
                            <div class="col-lg-5">
                                <label class="form-label">Journal *</label>
                                <select name="journal_type_id" class="form-select" required>
                                    <option value="">-- Choisir un journal {{ match($natureJournal ?? null) {'caisse' => 'de caisse', 'banque' => 'bancaire', 'mobile_money' => 'Mobile Money', default => 'de trésorerie'} }} --</option>
                                    @foreach($journalTypes as $journal)
                                        <option value="{{ $journal->id }}" {{ (string) old('journal_type_id') === (string) $journal->id ? 'selected' : '' }}>
                                            {{ $journal->code }} — {{ $journal->compte?->compte }} {{ $journal->compte?->designation }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label">Date *</label>
                                <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label class="form-label">Type *</label>
                                <select name="type" class="form-select" required>
                                    @foreach(['recette'=>'Recette','depense'=>'Dépense','achat'=>'Achat','vente'=>'Vente','od'=>'Opération diverse'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('type', 'recette') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label class="form-label">Monnaie *</label>
                                <select name="monnaie" class="form-select" required>
                                    <option value="CDF" {{ old('monnaie', 'CDF') === 'CDF' ? 'selected' : '' }}>CDF</option>
                                    <option value="USD" {{ old('monnaie') === 'USD' ? 'selected' : '' }}>USD</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label d-block">Traitement TVA *</label>
                                <div class="btn-group tva-choice" role="group" aria-label="Choix TVA">
                                    <input type="radio" class="btn-check" name="appliquer_tva" id="sans_tva" value="0" {{ $appliquerTva === '0' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary" for="sans_tva"><i class="bi bi-slash-circle me-1"></i> Sans TVA</label>
                                    <input type="radio" class="btn-check" name="appliquer_tva" id="avec_tva" value="1" {{ $appliquerTva === '1' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary" for="avec_tva"><i class="bi bi-percent me-1"></i> Avec TVA</label>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-title"><span>2</span><div><h6>Compte et partenaire</h6><small>Imputation et informations du tiers</small></div></div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Compte d’opération *</label>
                                <select name="liste_des_comptes_id" class="form-select" required>
                                    <option value="">-- Sélectionner un compte --</option>
                                    @foreach($comptes as $compte)
                                        <option value="{{ $compte->id }}" {{ (string) old('liste_des_comptes_id') === (string) $compte->id ? 'selected' : '' }}>
                                            {{ $compte->compte }} — {{ $compte->designation }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nom du partenaire *</label>
                                <input type="text" name="nom_partenaire" class="form-control" value="{{ old('nom_partenaire') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Téléphone *</label>
                                <input type="text" name="telephone_partenaire" class="form-control" value="{{ old('telephone_partenaire') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Adresse *</label>
                                <input type="text" name="adresse_partenaire" class="form-control" value="{{ old('adresse_partenaire') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Libellé de l’opération *</label>
                                <textarea name="description" rows="3" class="form-control" required>{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-title"><span>3</span><div><h6>Montants et paiement</h6><small>Valorisation de l’écriture</small></div></div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Mode de paiement *</label>
                                <select name="mode_paiement" class="form-select" required>
                                    <option value="espèces" {{ old('mode_paiement', 'espèces') === 'espèces' ? 'selected' : '' }}>Espèces</option>
                                    <option value="banque" {{ old('mode_paiement') === 'banque' ? 'selected' : '' }}>Banque</option>
                                    <option value="mobile_money" {{ old('mode_paiement') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><span id="libelle_montant">Montant principal</span> *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-cash"></i></span>
                                    <input type="number" step="0.01" min="0.01" name="montant_ttc" id="montant_ttc" class="form-control" value="{{ old('montant_ttc') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4 tva-field">
                                <label class="form-label">Taux TVA (%)</label>
                                <input type="number" name="taux_tva" id="taux_tva" step="0.01" min="0" max="100" value="{{ old('taux_tva', 16) }}" class="form-control">
                            </div>
                            <div class="col-md-6 tva-field">
                                <label class="form-label">Montant HT</label>
                                <input type="text" id="montant_ht" class="form-control calculated-field" readonly>
                            </div>
                            <div class="col-md-6 tva-field">
                                <label class="form-label">Montant TVA</label>
                                <input type="text" id="montant_tva" class="form-control calculated-field" readonly>
                            </div>
                            <div class="col-12">
                                <div class="form-check border rounded p-3 ps-5 bg-light">
                                    <input class="form-check-input" type="checkbox" name="regroupement_quotidien" value="1" id="regroupement_quotidien" {{ old('regroupement_quotidien') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="regroupement_quotidien">Regrouper à la clôture journalière</label>
                                    <div class="form-text">Le journal restera en attente, sans bon ni écriture, jusqu’à la clôture de la journée.</div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-title"><span>4</span><div><h6>Pièce et aperçu comptable</h6><small>Justificatif et contrôle de l’équilibre</small></div></div>
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label">Pièce justificative</label>
                                <input type="file" name="piece_justificatif" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">Formats acceptés : PDF, JPG et PNG.</small>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="card-footer journal-actions d-flex flex-column-reverse flex-sm-row justify-content-between gap-2">
                    <a href="{{ route('journaux.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i> Annuler</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Enregistrer l’écriture</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.journal-form{max-width:1180px}.page-kicker{display:block;color:#2563eb;font-size:.7rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;margin-bottom:.25rem}.journal-main-card{overflow:hidden}.journal-card-header{padding:1.15rem 1.4rem;background:linear-gradient(135deg,#132b4f,#1f4e87);color:#fff;border:0}.journal-card-header h5{color:#fff}.journal-card-header small{color:#cbdcf2}.rate-chip{display:flex;align-items:center;gap:.6rem;padding:.55rem .8rem;border:1px solid rgba(255,255,255,.18);border-radius:10px;background:rgba(255,255,255,.1);font-size:.84rem;white-space:nowrap}.rate-chip i{color:#93c5fd;font-size:1.1rem}.form-section{padding:1.4rem;border-bottom:1px solid #e8edf4}.form-section:last-child{border-bottom:0}.section-title{display:flex;align-items:center;gap:.75rem;margin-bottom:1.15rem}.section-title>span{width:32px;height:32px;display:grid;place-items:center;flex:0 0 32px;border-radius:9px;background:#eff6ff;color:#2563eb;font-weight:700}.section-title h6{margin:0;font-weight:700}.section-title small{color:#718096}.tva-choice .btn{min-width:115px}.calculated-field{background:#f4f7fb!important;font-weight:700;color:#1d4ed8!important}.journal-actions{padding:1rem 1.4rem;background:#fafbfd;border-top:1px solid #e5eaf1}@media(max-width:767px){.form-section{padding:1.1rem}.journal-card-header,.journal-actions{padding:1rem}.rate-chip{white-space:normal;width:100%}}
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const amount = document.getElementById('montant_ttc');
    const rate = document.getElementById('taux_tva');
    const htField = document.getElementById('montant_ht');
    const taxField = document.getElementById('montant_tva');
    const amountLabel = document.getElementById('libelle_montant');
    const typeSelect = document.querySelector('select[name="type"]');
    const currencySelect = document.querySelector('select[name="monnaie"]');
    const hiddenJournal = document.querySelector('input[name="journal_type_id"]');
    const journalOptions = @json($journalTypes->map(fn ($journal) => ['id' => $journal->id, 'monnaie' => $journal->monnaie ?? 'CDF'])->values());
    const money = value => Number(value || 0).toFixed(2);

    function synchroniserJournal() {
        if (!hiddenJournal) return;
        const journal = journalOptions.find(item => item.monnaie === currencySelect.value);
        hiddenJournal.value = journal ? journal.id : '';
    }

    function refresh() {
        const withTax = document.querySelector('input[name="appliquer_tva"]:checked')?.value === '1';
        const total = parseFloat(amount.value) || 0;
        const taxRate = withTax ? (parseFloat(rate.value) || 0) : 0;
        const ht = total > 0 ? total / (1 + taxRate / 100) : 0;
        const tax = total - ht;
        document.querySelectorAll('.tva-field').forEach(field => field.style.display = withTax ? '' : 'none');
        amountLabel.textContent = withTax ? 'Montant TTC' : 'Montant principal';
        htField.value = money(ht);
        taxField.value = money(tax);
    }

    [amount, rate, typeSelect].forEach(element => {
        element.addEventListener(element.tagName === 'SELECT' ? 'change' : 'input', refresh);
    });
    currencySelect.addEventListener('change', synchroniserJournal);
    document.querySelectorAll('input[name="appliquer_tva"]').forEach(input => input.addEventListener('change', refresh));
    synchroniserJournal();
    refresh();
});
</script>
@endpush
