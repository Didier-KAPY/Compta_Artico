@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-file-earmark-text me-2"></i>BRC</strong>
            @if(auth()->user()->hasRole(['Super Admin', 'Admin', 'Comptable', 'Chargé des finances']))
                <a href="{{ route('brc.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Nouveau BRC</a>
            @endif
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>Date</th><th>Référence</th><th>Compte débit</th><th>Compte crédit</th><th>Libellé</th><th>Monnaie</th><th class="text-end">Montant</th><th>Statut</th><th>Validé par</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    @forelse($brcs as $brc)
                        <tr>
                            <td>{{ $brc->date->format('d/m/Y') }}</td>
                            <td class="fw-semibold">{{ $brc->reference }}</td>
                            @php
                                $compteJournal = trim(($brc->journalType?->compte?->compte ?? '—').' — '.($brc->journalType?->compte?->designation ?? ''));
                                $comptesImputation = $brc->lignes->map(fn($ligne) => trim(($ligne->compte?->compte ?? '—').' — '.($ligne->compte?->designation ?? '')))->unique()->implode(' / ');
                            @endphp
                            <td>{{ $brc->sens === 'debit' ? $compteJournal : ($comptesImputation ?: '—') }}</td>
                            <td>{{ $brc->sens === 'credit' ? $compteJournal : ($comptesImputation ?: '—') }}</td>
                            <td>{{ $brc->lignes->pluck('libelle')->filter()->unique()->implode(' / ') ?: '—' }}</td>
                            <td>{{ $brc->monnaie }}</td>
                            <td class="text-end">{{ number_format($brc->total, 2, ',', ' ') }}</td>
                            <td><span class="badge {{ $brc->statut === 'Validé' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $brc->statut }}</span></td>
                            <td>{{ trim(($brc->validateur?->prenom ?? '').' '.($brc->validateur?->nom ?? '')) ?: '—' }}</td>
                            <td>
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('brc.show', $brc) }}" title="Voir le BRC"><i class="bi bi-eye"></i></a>
                                <a class="btn btn-outline-danger btn-sm" href="{{ route('brc.pdf', $brc) }}" title="Télécharger le bon en PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                <a class="btn btn-outline-success btn-sm" href="{{ route('brc.excel', $brc) }}" title="Télécharger le bon en Excel"><i class="bi bi-file-earmark-excel"></i></a>
                                @if($brc->statut === 'En attente' && auth()->user()->hasRole(['Super Admin', 'Comptable']))
                                    <form method="POST" action="{{ route('brc.valider', $brc) }}" data-confirm="Valider ce BRC et générer ses écritures ?">
                                        @csrf
                                        <button class="btn btn-success btn-sm"><i class="bi bi-check-circle me-1"></i>Valider</button>
                                    </form>
                                @elseif($brc->journal_id)
                                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('journaux.show', $brc->journal_id) }}"><i class="bi bi-eye"></i></a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Aucun BRC enregistré.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $brcs->links() }}
        </div>
    </div>
</div>
@endsection
