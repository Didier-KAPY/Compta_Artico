@extends('layouts.app')

@section('content')

@php
    $isSuperAdmin = auth()->user()?->hasRole('Super Admin') ?? false;
    $canManageJournaux = auth()->user()?->can('manageJournaux') ?? false;
@endphp

<div class="container py-4">

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3">

    <h4 class="mb-0">
        <i class="bi bi-journal-bookmark me-2"></i>
        Journaux de trésorerie
    </h4>

    <div class="d-flex gap-2 flex-wrap">
        @if($canManageJournaux)
        @include('partials.period-export-buttons', ['rapport' => 'journaux'])
        <a href="{{ route('journaux.create.caisse') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-cash-stack me-1"></i>Journal Caisse</a>
        <a href="{{ route('journaux.create.banque') }}" class="btn btn-outline-success btn-sm"><i class="bi bi-bank me-1"></i>Journal Banque</a>
        <a href="{{ route('journaux.create.mobile') }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-phone me-1"></i>Journal Mobile Money</a>
        @endif
    </div>

</div>

<!-- FILTRES -->
<!-- BOUTON FILTRE -->
<div class="mb-3">

    <button class="btn btn-outline-primary"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#zoneFiltre">

        <i class="bi bi-funnel"></i>
        Afficher les filtres
    </button>
</div>


<!-- FILTRES CACHÉS -->
<div class="collapse" id="zoneFiltre">

    <div class="card shadow-sm border-0 mb-3">

        <div class="card-body">

            <form method="GET" action="{{ route('journaux.index') }}">

                <div class="row g-3">


                    <div class="col-md-4">

                        <label class="form-label">
                            Référence
                        </label>

                        <input type="text"
                               name="reference"
                               class="form-control"
                               value="{{ request('reference') }}"
                               placeholder="Référence">

                    </div>



                    <div class="col-md-3">

                        <label class="form-label">
                            Date début
                        </label>

                        <input type="date"
                               name="date_debut"
                               class="form-control"
                               value="{{ request('date_debut') }}">

                    </div>




                    <div class="col-md-3">

                        <label class="form-label">
                            Date fin
                        </label>

                        <input type="date"
                               name="date_fin"
                               class="form-control"
                               value="{{ request('date_fin') }}">

                    </div>




                    <div class="col-md-4">
                        <label class="form-label">Journal / compte de trésorerie</label>
                        <select name="journal_type_id" class="form-select">
                            <option value="">Tous les journaux de trésorerie</option>
                            @foreach($journalTypesTresorerie as $typeJournal)
                                <option value="{{ $typeJournal->id }}" {{ (string) request('journal_type_id') === (string) $typeJournal->id ? 'selected' : '' }}>
                                    {{ $typeJournal->code }} — {{ $typeJournal->monnaie ?? 'CDF' }} — {{ $typeJournal->compte?->compte }} {{ $typeJournal->compte?->designation }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">

                        <button type="submit"
                                class="btn btn-primary w-100">

                            <i class="bi bi-search"></i>
                            Filtrer

                        </button>

                    </div>


                </div>



                <div class="mt-3">

                    <a href="{{ route('journaux.index') }}"
                       class="btn btn-secondary">

                        <i class="bi bi-arrow-clockwise"></i>
                        Réinitialiser

                    </a>

                </div>


            </form>


        </div>

    </div>

</div>

<!-- TABLEAU -->
<div class="card shadow-sm border-0">

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-dark">
                    <tr>
                        <th>Référence</th>
                        @if($isSuperAdmin)
                            <th>Validé par</th>
                        @endif
                        <th>Date</th>
                        <th>Description</th>
                        <th>Monnaie</th>
                        <th class="text-end">Entrées CDF</th>
                        <th class="text-end">Sorties CDF</th>
                        <th class="text-end">Entrées USD</th>
                        <th class="text-end">Sorties USD</th>
                        <th>Statut</th>
                        @if($canManageJournaux)<th class="text-center">Actions</th>@endif
                    </tr>
                </thead>

                <tbody>

                    @forelse($journaux as $journal)

                    <tr>

                        <td>
                            <strong>{{ $journal->reference ?? '-' }}</strong>
                        </td>

                        @if($isSuperAdmin)
                            <td>{{ trim(($journal->validateur?->prenom ?? '').' '.($journal->validateur?->nom ?? '')) ?: 'Non validé' }}</td>
                        @endif

                        <td>
                            {{ $journal->date 
                                ? \Carbon\Carbon::parse($journal->date)->format('d/m/Y') 
                                : '-' 
                            }}
                        </td>


                        <td>
                            {{ $journal->description ?? '-' }}
                        </td>


                        <td>
                            <span class="badge bg-secondary">
                                {{ $journal->monnaie ?? 'CDF' }}
                            </span>
                        </td>


                        <td class="text-end fw-bold text-success">
                            {{ number_format($journal->entrees_cdf ?? 0, 2, ',', ' ') }}
                        </td>


                        <td class="text-end fw-bold text-danger">
                            {{ number_format($journal->sorties_cdf ?? 0, 2, ',', ' ') }}
                        </td>


                        <td class="text-end fw-bold text-success">
                            {{ number_format($journal->entrees_usd ?? 0, 2, ',', ' ') }}
                        </td>


                        <td class="text-end fw-bold text-danger">
                            {{ number_format($journal->sorties_usd ?? 0, 2, ',', ' ') }}
                        </td>


                       <td>
                            @php
                                $statut = trim(mb_strtolower($journal->statut));
                            @endphp


                            @if($statut == 'validé' || $statut == 'valide')

                                <span class="badge bg-success">
                                    Validé
                                </span>


                            @elseif($statut == 'rejeté' || $statut == 'rejete')

                                <span class="badge bg-danger">
                                    Rejeté
                                </span>


                            @else

                                <span class="badge bg-warning text-dark">
                                    En attente
                                </span>

                            @endif

                            </td>

                        @if($canManageJournaux)
                        <td class="text-center">

                            <div class="dropdown">

                                <button class="btn btn-sm btn-light border dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown">

                                    Actions

                                </button>


                                <ul class="dropdown-menu">


                                    <li>
                                        <a class="dropdown-item"
                                        href="{{ route('journaux.show',$journal->id) }}">

                                            <i class="bi bi-eye me-2"></i>
                                            Voir

                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="{{ route('journaux.recu.pdf', $journal->id) }}">
                                            <i class="bi bi-file-earmark-arrow-down me-2"></i>Télécharger le reçu
                                        </a>
                                    </li>

                                    @can('update', $journal)
                                    <li><a class="dropdown-item" href="{{ route('journaux.edit', $journal->id) }}"><i class="bi bi-pencil-square me-2"></i>Modifier</a></li>
                                    @endcan

                                    <li>

                                        <a class="dropdown-item"
                                        href="{{ route('journaux.recu',$journal->id) }}"
                                        target="_blank">

                                            <i class="bi bi-printer me-2"></i>
                                            Imprimer reçu

                                        </a>

                                    </li>


                                    <li>

                                        @if($journal->piece_justificatif)

                                            <a class="dropdown-item"
                                            href="{{ route('journaux.piece', $journal) }}"
                                            target="_blank">

                                                <i class="bi bi-paperclip me-2"></i>
                                                Voir pièce justificative

                                            </a>

                                        @else

                                            <span class="dropdown-item text-muted">

                                                <i class="bi bi-paperclip me-2"></i>
                                                Aucune pièce jointe

                                            </span>

                                        @endif

                                    </li>


                                </ul>

                            </div>

                        </td>
                        @endif


                    </tr>


                    @empty

                    <tr>

                        <td colspan="{{ $isSuperAdmin ? 11 : ($canManageJournaux ? 10 : 9) }}" class="text-center text-muted py-4">

                            Aucun journal trouvé

                        </td>

                    </tr>

                    @endforelse


                    </tbody>
            </table>
        </div>
    </div>
</div>


<!-- PAGINATION -->
<div class="mt-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
    <div class="text-muted small">
        @if($journaux->total() > 0)
            Affichage de {{ $journaux->firstItem() }} à {{ $journaux->lastItem() }}
            sur {{ $journaux->total() }} journaux
        @else
            Aucun journal
        @endif
    </div>
    <div>
        {{ $journaux->onEachSide(1)->links() }}
    </div>
</div>
</div>

@endsection
