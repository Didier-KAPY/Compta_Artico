@extends('layouts.app')
@section('title', 'Liste des écritures comptables')
@section('content')
@php $isSuperAdmin = auth()->user()?->hasRole('Super Admin') ?? false; @endphp
<div class="container-fluid">
    {{-- Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>
        </div>
    @endif
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-journal-check me-2"></i>
                Liste des écritures comptables
            </h5>
            @include('partials.period-export-buttons', ['rapport' => 'ecritures'])
        </div>
        <div class="card-body">
            {{-- ========================= --}}
            {{-- FILTRE                    --}}
            {{-- ========================= --}}
            <form
                method="GET"
                action="{{ route('ecritures.liste') }}"
                class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        Date début
                    </label>
                    <input
                        type="date"
                        name="date_debut"
                        class="form-control"
                        value="{{ request('date_debut') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        Date fin
                    </label>
                    <input
                        type="date"
                        name="date_fin"
                        class="form-control"
                        value="{{ request('date_fin') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button
                        class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i>
                        Filtrer
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="{{ route('ecritures.create') }}"
                    class="btn btn-info text-white w-100">
                        <i class="bi bi-diagram-3"></i>
                        Imputation des comptes
                    </a>
                </div>
            </form>      
        </div>
            {{-- ========================= --}}
            {{-- TABLEAU                   --}}
            {{-- ========================= --}}
            <div class="table-responsive">
                <table
                    class="table table-bordered table-hover table-striped align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        @if($isSuperAdmin)
                            <th>Validé par</th>
                        @endif
                        <th>Compte</th>
                        <th>Désignation</th>
                        <th class="text-end">
                            Débit CDF
                        </th>
                        <th class="text-end">
                            Crédit CDF
                        </th>
                       
                        <th class="text-center">
                            Statut
                        </th>
                        <th class="text-center">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($ecritures as $ecriture)
                        <tr>
                            <td>
                                {{ $ecriture->date->format('d/m/Y') }}
                            </td>
                            @if($isSuperAdmin)
                                <td>{{ trim(($ecriture->validateur?->prenom ?? '').' '.($ecriture->validateur?->nom ?? '')) ?: 'Non validé' }}</td>
                            @endif
                            <td>
                                {{ $ecriture->compte->compte ?? '-' }}
                            </td>
                            <td>
                                {{ $ecriture->compte->designation ?? '-' }}
                            </td>
                          
                            <td class="text-end">
                                {{ number_format($ecriture->debit_cdf,2,',',' ') }}
                            </td>
                            <td class="text-end">
                                {{ number_format($ecriture->credit_cdf,2,',',' ') }}
                            </td>

                            <td class="text-center">

                                @if($ecriture->statut == 'Validé')

                                    <span class="badge bg-success">
                                        Validé
                                    </span>

                                @elseif($ecriture->statut == 'Rejeté')

                                    <span class="badge bg-danger">
                                        Rejeté
                                    </span>

                                @else

                                    <span class="badge bg-warning text-dark">
                                        En attente
                                    </span>

                                @endif

                            </td>


                            <td class="text-center">

                                @can('valider', $ecriture)
                                @if($ecriture->statut === 'En attente')
                                {{-- Valider --}}
                                <form
                                    action="{{ route('ecritures.valider', $ecriture->id) }}"
                                    method="POST"
                                    enctype="multipart/form-data"
                                    class="d-inline-flex align-items-center gap-1">

                                    @csrf

                                    <input type="file"
                                           name="piece_justificative"
                                           class="form-control form-control-sm"
                                           style="max-width:180px"
                                           accept=".pdf,.jpg,.jpeg,.png"
                                           @required(str_starts_with(mb_strtoupper(trim((string) $ecriture->piece)), 'BSC'))
                                           title="Pièce justificative{{ str_starts_with(mb_strtoupper(trim((string) $ecriture->piece)), 'BSC') ? ' obligatoire' : ' facultative' }}">

                                    <button type="submit"
                                            class="btn btn-sm btn-success"
                                            title="Valider">

                                        <i class="bi bi-check-circle"></i>

                                    </button>

                                </form>
                                @endif
                                @endcan

                                @if($ecriture->piece_justificative)
                                <a href="{{ route('ecritures.piece', $ecriture->id) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-info"
                                   title="Visualiser la pièce justificative">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endif

                                @can('update', $ecriture)
                                {{-- Modifier --}}
                                <a
                                    href="{{ route('ecritures.edit', $ecriture->id) }}"
                                    class="btn btn-sm btn-warning"
                                    title="Modifier">

                                    <i class="bi bi-pencil-square"></i>

                                </a>
                                @endcan

                                <a href="{{ route('ecritures.show', $ecriture) }}" class="btn btn-sm btn-outline-primary" title="Voir"><i class="bi bi-eye"></i></a>

                                @can('reouvrir', $ecriture)
                                @if($ecriture->statut === 'Validé')
                                <form action='{{ route('ecritures.reouvrir', $ecriture->id) }}'
                                      method='POST' class='d-inline'>
                                    @csrf
                                    @method('PATCH')
                                    <button type='submit' class='btn btn-sm btn-warning'
                                            title='Réouvrir'>
                                        <i class='bi bi-arrow-counterclockwise'></i>
                                    </button>
                                </form>
                                @endif
                                @endcan

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="{{ $isSuperAdmin ? 8 : 7 }}"
                                class="text-center text-muted">
                                Aucune écriture comptable trouvée.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="{{ $isSuperAdmin ? 4 : 3 }}" class="text-end">
                                Totaux CDF
                            </th>
                            <th class="text-end">
                                {{ number_format($totalDebitCDF,2,',',' ') }}
                            </th>
                            <th class="text-end">
                                {{ number_format($totalCreditCDF,2,',',' ') }}
                            </th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            {{-- Vérification de l'équilibre comptable --}}
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-calculator"></i>
                    Vérification de l'équilibre comptable
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            @if($equilibreCDF == 0)
                                <div class="alert alert-success mb-0">
                                    <strong>CDF :</strong>
                                    Les écritures sont équilibrées.
                                </div>
                            @else
                                <div class="alert alert-danger mb-0">
                                    <strong>CDF :</strong>
                                    Écart de
                                    {{ number_format($equilibreCDF,2,',',' ') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                {{ $ecritures->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
