@extends('layouts.app')
@section('title', 'Constatation comptable')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3"><h3>Constatation comptable {{ $constatation?->numero }}</h3><a href="{{ route('ecritures.show', $source) }}" class="btn btn-secondary">Retour à l’écriture</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card border-0 shadow-sm mb-4"><div class="card-header"><strong>Opération d’origine</strong></div><div class="card-body row g-3">
        @foreach([
            'Écriture source'=>'#'.$snapshot['source_id'], 'Pièce'=>$snapshot['piece'], 'Type de pièce'=>$snapshot['type_piece'],
            'Date'=>\Carbon\Carbon::parse($snapshot['date'])->format('d/m/Y'), 'Libellé'=>$snapshot['libelle'],
            'Montant USD'=>number_format($snapshot['montant_usd'],2,',',' ').' USD', 'Montant CDF'=>number_format($snapshot['montant_cdf'],2,',',' ').' CDF',
            'Taux de change utilisé'=>$snapshot['taux_change'] ? number_format($snapshot['taux_change'],8,',',' ').' CDF / USD (équivalent enregistré)' : 'Sans conversion USD',
            'Journal'=>($snapshot['journal'] ?? '—').' #'.$snapshot['journal_id'], 'Entreprise'=>$entrepriseActive->nom_entreprise,
            'Créateur'=>$snapshot['createur'], 'Compte de trésorerie'=>$snapshot['compte_tresorerie'], 'Statut source'=>$source->statut,
        ] as $label=>$value)<div class="col-md-4"><small class="text-muted d-block">{{ $label }}</small><strong>{{ $value }}</strong></div>@endforeach
    </div></div>
    @if($constatation)
        <div class="card border-0 shadow-sm"><div class="card-header d-flex justify-content-between"><strong>{{ $constatation->numero }} — Lignes de constatation</strong><span class="badge bg-success">{{ $constatation->statut }}</span></div>
        <div class="card-body">Date comptable : {{ $constatation->date->format('d/m/Y') }} — Enregistrée le {{ $constatation->created_at->format('d/m/Y H:i') }} par {{ $constatation->user?->prenom }} {{ $constatation->user?->nom }} — Pièce d’origine : {{ $constatation->piece_origine }}</div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Nature</th><th>Compte</th><th>Libellé</th><th class="text-end">Débit CDF</th><th class="text-end">Crédit CDF</th></tr></thead><tbody>
        @foreach($constatation->lignes as $line)<tr><td>{{ $line->nature_constatation }}</td><td>{{ $line->compte?->compte }} — {{ $line->compte?->designation }}</td><td>{{ $line->libelle }}</td><td class="text-end">{{ number_format($line->debit_cdf,2,',',' ') }}</td><td class="text-end">{{ number_format($line->credit_cdf,2,',',' ') }}</td></tr>@endforeach
        </tbody><tfoot><tr><th colspan="3">TOTAL</th><th class="text-end">{{ number_format($constatation->lignes->sum('debit_cdf'),2,',',' ') }}</th><th class="text-end">{{ number_format($constatation->lignes->sum('credit_cdf'),2,',',' ') }}</th></tr></tfoot></table></div></div>
    @elseif(auth()->user()?->hasRole(['Super Admin', 'Comptable']))
    <form method="POST" action="{{ route('ecritures.constatation.store', $source) }}" id="constatation-form" class="card border-0 shadow-sm">
        @csrf
        <div class="card-header"><strong>Saisir la constatation</strong></div><div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3"><label class="form-label" for="date">Date de constatation</label><input id="date" type="date" name="date" value="{{ old('date', $snapshot['date']) }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label" for="type_operation">Type d’opération</label><select id="type_operation" name="type_operation" class="form-select">@foreach(['generique'=>'Autre opération', 'charge'=>'Constatation de charge', 'produit'=>'Constatation de produit'] as $type=>$label)<option value="{{ $type }}" @selected(old('type_operation', 'generique') === $type)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label" for="compte_liaison_id">Compte de dette / créance à solder par le règlement</label><select id="compte_liaison_id" name="compte_liaison_id" class="form-select" required><option value="">Choisir le compte de liaison</option>@foreach($comptes as $account)<option value="{{ $account->id }}" @selected(old('compte_liaison_id') == $account->id)>{{ $account->compte }} — {{ $account->designation }}</option>@endforeach</select></div>
            </div>
            <p class="text-muted">La constatation et le règlement sont enregistrés séparément. Le mouvement de trésorerie existant sera conservé ; seule sa contrepartie sera ajoutée. Tous les montants saisis sont en CDF.</p>
            <div id="constatation-lines">
            @foreach(old('lignes', [['nature'=>'autre'], ['nature'=>'autre']]) as $index=>$line)
                <div class="constatation-line border rounded p-3 mb-3"><div class="row g-2 align-items-end">
                    <input type="hidden" name="lignes[{{ $index }}][nature]" value="autre">
                    <div class="col-md-4"><label class="form-label">Compte</label><select name="lignes[{{ $index }}][liste_des_comptes_id]" class="form-select account" required><option value="">Choisir un compte</option>@foreach($comptes as $account)<option value="{{ $account->id }}" data-code="{{ $account->compte }}" @selected(($line['liste_des_comptes_id'] ?? '') == $account->id)>{{ $account->compte }} — {{ $account->designation }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Libellé</label><input name="lignes[{{ $index }}][libelle]" value="{{ $line['libelle'] ?? '' }}" placeholder="Décrire l’opération constatée" class="form-control" maxlength="255" required></div>
                    <div class="col-md-2"><label class="form-label">Débit CDF</label><input type="number" name="lignes[{{ $index }}][debit_cdf]" value="{{ $line['debit_cdf'] ?? 0 }}" class="form-control debit" min="0" max="9999999999999" step="0.01" required></div>
                    <div class="col-md-2"><label class="form-label">Crédit CDF</label><input type="number" name="lignes[{{ $index }}][credit_cdf]" value="{{ $line['credit_cdf'] ?? 0 }}" class="form-control credit" min="0" max="9999999999999" step="0.01" required></div>
                </div><button type="button" class="btn btn-sm btn-outline-danger remove-line mt-2">Supprimer la ligne</button></div>
            @endforeach
            </div>
            <button type="button" id="add-line" class="btn btn-outline-primary mb-3">+ Ajouter une ligne</button>
            <div class="row g-3 mb-3">@foreach(['debit'=>'Total Débit', 'credit'=>'Total Crédit', 'gap'=>'Écart', 'net'=>'Dette / créance constatée', 'payment-gap'=>'Écart règlement'] as $id=>$label)<div class="col-md-3"><small class="text-muted d-block">{{ $label }}</small><strong id="total-{{ $id }}">0,00 CDF</strong></div>@endforeach</div>
            <div id="balance-message" class="alert alert-warning" role="status" aria-live="polite"></div>
            <button type="submit" id="validate-constatation" class="btn btn-success" disabled>Valider la constatation</button>
        </div>
    </form>
    @endif
    <div class="card border-0 shadow-sm mt-4 mb-4"><div class="card-header"><strong>Écritures de règlement enregistrées</strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Compte</th><th>Libellé</th><th class="text-end">Débit CDF</th><th class="text-end">Crédit CDF</th><th>Statut</th></tr></thead><tbody>
    @foreach($lignesExistantes as $line)<tr><td>{{ $line->compte?->compte }} — {{ $line->compte?->designation }}</td><td>{{ $line->libelle }}</td><td class="text-end">{{ number_format($line->debit_cdf,2,',',' ') }}</td><td class="text-end">{{ number_format($line->credit_cdf,2,',',' ') }}</td><td>{{ $line->statut }}</td></tr>@endforeach
    </tbody></table></div></div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('constatation-form');
    if (!form) return;
    const container = document.getElementById('constatation-lines');
    const template = container.firstElementChild.cloneNode(true);
    const paid = Math.round(Number(@json($snapshot['montant_cdf'])) * 100);
    const outgoing = @json((float)$source->credit_cdf > 0);
    const treasuryAccount = @json((string)$source->liste_des_comptes_id);
    const button = document.getElementById('validate-constatation');
    const message = document.getElementById('balance-message');
    const fmt = value => (value / 100).toLocaleString('fr-FR', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' CDF';
    function refresh() {
        let debit=0, credit=0, net=0, valid=true;
        const link = form.elements.compte_liaison_id.value;
        const rows = [...container.children];
        rows.forEach((row,index) => {
            row.querySelectorAll('[name]').forEach(field => field.name=field.name.replace(/lignes\[\d+\]/, `lignes[${index}]`));
            const d=Math.round(Number(row.querySelector('.debit').value)*100), c=Math.round(Number(row.querySelector('.credit').value)*100);
            const account=row.querySelector('.account').value;
            debit+=d; credit+=c;
            if (!account || account===treasuryAccount || !((d>0 && c===0)||(c>0 && d===0))) valid=false;
            if (account===link) net+=outgoing ? c-d : d-c;
        });
        Object.entries({debit,credit,gap:debit-credit,net,'payment-gap':net-paid}).forEach(([key,value])=>document.getElementById('total-'+key).textContent=fmt(value));
        const balanced=debit>0 && debit===credit;
        const matches=net===paid;
        button.disabled=!(balanced && matches && valid && link && rows.length>=2 && form.checkValidity());
        message.className='alert '+(button.disabled ? 'alert-warning' : 'alert-success');
        message.textContent=!balanced ? 'L’écriture n’est pas équilibrée. Veuillez vérifier les montants avant de continuer.' : !matches ? 'La dette ou créance nette ne correspond pas au règlement enregistré.' : button.disabled ? 'Vérifiez les comptes et les champs obligatoires.' : 'Constatation équilibrée et règlement conforme : vous pouvez valider.';
    }
    document.getElementById('add-line').addEventListener('click',()=>{
        const row=template.cloneNode(true);
        row.querySelectorAll('select').forEach(field=>field.value='');
        row.querySelectorAll('input:not([type])').forEach(field=>field.value='');
        row.querySelectorAll('input[type=number]').forEach(field=>field.value='0');
        container.appendChild(row); refresh();
    });
    container.addEventListener('click',event=>{ if (event.target.closest('.remove-line')) {event.target.closest('.constatation-line').remove();refresh();} });
    form.addEventListener('input',refresh); form.addEventListener('change',refresh);
    form.addEventListener('submit',event=>{refresh();if(button.disabled) event.preventDefault();else {button.disabled=true;button.textContent='Enregistrement…';}});
    refresh();
});
</script>
@endpush
