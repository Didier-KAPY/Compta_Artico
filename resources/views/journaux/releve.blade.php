@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 sage-screen">
    <div class="sage-titlebar d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div><span class="sage-module">Trésorerie</span><h3><i class="bi bi-list-columns-reverse me-2"></i>Relevé des mouvements</h3><p>Grand livre auxiliaire des comptes de trésorerie</p></div>
        <div class="d-flex gap-2 no-print">@can('viewTreasurySituation')<a href="{{ route('journaux.tresorerie', request()->only('date_debut','date_fin')) }}" class="btn btn-outline-primary"><i class="bi bi-pie-chart me-1"></i> Situation</a>@endcan<button class="btn btn-light border" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimer</button></div>
    </div>

    <div class="d-flex justify-content-end mb-2 no-print">@include('partials.period-export-buttons', ['rapport'=>'releve','exportParams'=>['date_debut'=>$dateDebut,'date_fin'=>$dateFin,'journal_type_id'=>request('journal_type_id')]])</div>
    <form method="GET" action="{{ route('journaux.releve') }}" class="sage-filter no-print">
        <div class="row g-2 align-items-end"><div class="col-md-3 col-xl-2"><label class="form-label">Du</label><input type="date" name="date_debut" class="form-control" value="{{ $dateDebut }}"></div><div class="col-md-3 col-xl-2"><label class="form-label">Au</label><input type="date" name="date_fin" class="form-control" value="{{ $dateFin }}"></div><div class="col-md-4 col-xl-5"><label class="form-label">Journal / compte de trésorerie</label><select name="journal_type_id" class="form-select"><option value="">Tous les journaux de trésorerie</option>@foreach($comptesTresorerie as $journalType)<option value="{{ $journalType->id }}" {{ request('journal_type_id') == $journalType->id ? 'selected' : '' }}>{{ $journalType->code }} — {{ $journalType->compte?->compte }} {{ $journalType->compte?->designation }}</option>@endforeach</select></div><div class="col-md-2 col-xl-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Appliquer</button><a href="{{ route('journaux.releve') }}" class="btn btn-light border"><i class="bi bi-x-lg"></i></a></div></div>
    </form>

    <div class="sage-panel mt-3">
        <div class="sage-panel-head"><div><strong>Relevé du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</strong><small>{{ $journaux->total() }} mouvement(s) validé(s)</small></div><span>Montants par devise</span></div>
        <div class="table-responsive"><table class="table sage-grid statement-grid mb-0"><thead><tr><th>Date</th><th>Pièce / Référence</th><th>Journal</th><th>Compte</th><th>Libellé</th><th class="text-end">Entrée CDF</th><th class="text-end">Sortie CDF</th><th class="text-end">Solde CDF</th><th class="text-end">Entrée USD</th><th class="text-end">Sortie USD</th><th class="text-end">Solde USD</th></tr></thead><tbody>
            <tr class="opening-row"><td>{{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }}</td><td colspan="4">SOLDE D’OUVERTURE</td><td></td><td></td><td class="text-end">{{ number_format($ouverture->cdf,2,',',' ') }}</td><td></td><td></td><td class="text-end">{{ number_format($ouverture->usd,2,',',' ') }}</td></tr>
            @forelse($journaux as $journal)
            <tr><td>{{ $journal->date?->format('d/m/Y') }}</td><td>@can('manageJournaux')<a href="{{ route('journaux.show',$journal->id) }}">{{ $journal->reference }}</a>@else{{ $journal->reference }}@endcan</td><td><span class="journal-code">{{ $journal->journalType?->code ?? '—' }}</span></td><td>{{ $journal->journalType?->compte?->compte ?? '—' }}</td><td class="description-cell" title="{{ $journal->description }}">{{ $journal->description }}</td><td class="text-end amount-in">{{ number_format($journal->entrees_cdf,2,',',' ') }}</td><td class="text-end amount-out">{{ number_format($journal->sorties_cdf,2,',',' ') }}</td><td class="text-end fw-semibold">{{ number_format($journal->solde_progressif_cdf,2,',',' ') }}</td><td class="text-end amount-in">{{ number_format($journal->entrees_usd,2,',',' ') }}</td><td class="text-end amount-out">{{ number_format($journal->sorties_usd,2,',',' ') }}</td><td class="text-end fw-semibold">{{ number_format($journal->solde_progressif_usd,2,',',' ') }}</td></tr>
            @empty<tr><td colspan="11" class="empty-state"><i class="bi bi-inbox"></i>Aucun mouvement validé sur cette période.</td></tr>@endforelse
        </tbody><tfoot><tr><td colspan="5">MOUVEMENTS DE LA PÉRIODE</td><td class="text-end">{{ number_format($totaux['entree_cdf'],2,',',' ') }}</td><td class="text-end">{{ number_format($totaux['sortie_cdf'],2,',',' ') }}</td><td class="text-end">{{ number_format($totaux['solde_cdf'],2,',',' ') }}</td><td class="text-end">{{ number_format($totaux['entree_usd'],2,',',' ') }}</td><td class="text-end">{{ number_format($totaux['sortie_usd'],2,',',' ') }}</td><td class="text-end">{{ number_format($totaux['solde_usd'],2,',',' ') }}</td></tr></tfoot></table></div>
        <div class="p-3 no-print">{{ $journaux->links() }}</div>
    </div>
</div>
@include('journaux._sage_styles')
@endsection
