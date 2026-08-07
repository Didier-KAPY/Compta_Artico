@extends('layouts.app')

@section('content')

<div class="container py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-journal-bookmark me-2"></i>
                Détail du Journal Comptable
            </h4>
            <small class="text-muted">Consultation de l'écriture comptable</small>
        </div>
        <div class="d-flex gap-2">
            @can('deleteFinancialDocument')<button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalSuppressionDocument"><i class="bi bi-trash me-1"></i>Supprimer</button>@endcan
            <a href="{{ route('journaux.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
        </div>
    </div>

    @include('partials.document-navigation')
    @include('partials.financial-delete-modal', ['documentType'=>'Journal','documentReference'=>$journal->reference,'documentStatus'=>$journal->statut,'deleteRoute'=>route('journaux.destroy',$journal)])

    <!-- STATUT -->
    <div class="alert
        @if($journal->statut == 'Validé') alert-success
        @elseif($journal->statut == 'Rejeté') alert-danger
        @else alert-warning @endif
        d-flex justify-content-between align-items-center">

        <div><strong>Statut :</strong> {{ $journal->statut }}</div>
        <div><strong>Référence :</strong> {{ $journal->reference }}</div>
    </div>

    <!-- TAUX -->
    <div class="card shadow-sm mb-3 border-primary">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <strong>Taux du jour</strong>
                <div class="text-muted small">Dernier taux enregistré</div>
            </div>

            <h5 class="mb-0 text-primary">
                {{ number_format($tauxActuel->taux_de_change ?? 0, 2) }}
            </h5>
        </div>
    </div>

    <!-- INFOS -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">

            <div class="row text-center align-items-center g-3">

                <div class="col-md">
                    <strong>Date</strong><br>
                    {{ \Carbon\Carbon::parse($journal->date)->format('d/m/Y') }}
                </div>

                <div class="col-md">
                    <strong>Monnaie</strong><br>
                    {{ $journal->monnaie }}
                </div>

                <div class="col-md">
                    <strong>Mode Paiement</strong><br>
                    {{ $journal->mode_paiement ?? '-' }}
                </div>

                <div class="col-md">
                    <strong>Type</strong><br>
                    {{ $journal->type ?? '-' }}
                </div>

                <div class="col-md">
                    <strong>Créé par</strong><br>
                    {{ $journal->user->nom ?? 'Utilisateur' }}
                </div>

            </div>

        </div>
    </div>

    <!-- DESCRIPTION -->
    <div class="card shadow-sm mb-3">
        <div class="card-header"><strong>Description</strong></div>
        <div class="card-body">
            {{ $journal->description }}
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-paperclip text-primary"></i><strong>Pièce justificative</strong>
        </div>
        <div class="card-body">
            @if($pieceExiste)
                @if(str_starts_with($pieceMime, 'image/'))
                    <a href="{{ $pieceUrl }}" target="_blank" class="d-inline-block mb-3">
                        <img src="{{ $pieceUrl }}" alt="Pièce justificative {{ $journal->reference }}" class="img-fluid rounded border" style="max-height: 360px">
                    </a>
                @endif
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted">{{ basename($piecePath) }}</span>
                    <a href="{{ $pieceUrl }}" target="_blank" class="btn btn-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>Consulter la pièce
                    </a>
                    <a href="{{ $pieceUrl }}?download=1" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-download me-1"></i>Télécharger
                    </a>
                </div>
            @elseif($piecePath)
                <div class="alert alert-light border mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    Référence de la pièce : <strong>{{ $piecePath }}</strong>
                </div>
            @else
                <span class="text-muted"><i class="bi bi-file-earmark-x me-2"></i>Aucune pièce justificative jointe.</span>
            @endif
        </div>
    </div>

    @if($lignes->count())

<div class="card shadow-sm mb-3">

    <div class="card-header">

        @if($nature == 'Entree')

            Détails de l'entrée de caisse

        @elseif($nature == 'Sortie')

            Détails de la sortie de caisse

        @endif

    </div>


    <div class="card-body">

        <table class="table table-bordered">
            <thead>
            <tr>
                <th>Désignation</th>
                <th>Quantité</th>
                <th>Prix unitaire</th>
                <th>Montant</th>
            </tr>
            </thead>

            <tbody>


            @foreach($lignes as $ligne)

            <tr>

                <td>
                    {{ $ligne->designation }}
                </td>


                <td>
                    {{ $ligne->quantite }}
                </td>


                <td>
                    {{ number_format($ligne->prix_unitaire,2,',',' ') }}
                </td>


                <td>
                    {{ number_format($ligne->montant,2,',',' ') }}
                </td>

            </tr>


            @endforeach


            </tbody>


        </table>


    </div>

</div>

@endif

    <!-- MOUVEMENTS (CORRIGÉ ALIGNEMENT) -->
    <div class="card shadow-sm mb-3">
        <div class="card-header"><strong>Mouvements Comptables</strong></div>

        <div class="card-body">
            <div class="row text-center g-3">

                <div class="col-md-2">
                    <strong>Entrées CDF</strong>
                    <h5>{{ number_format($journal->entrees_cdf, 2) }}</h5>
                </div>

                <div class="col-md-2">
                    <strong>Sorties CDF</strong>
                    <h5>{{ number_format($journal->sorties_cdf, 2) }}</h5>
                </div>

                <div class="col-md-2">
                    <strong>Entrées USD</strong>
                    <h5>{{ number_format($journal->entrees_usd, 2) }}</h5>
                </div>

                <div class="col-md-2">
                    <strong>Sorties USD</strong>
                    <h5>{{ number_format($journal->sorties_usd, 2) }}</h5>
                </div>

                <div class="col-md-2">
                    <strong>Total Entrée CDF</strong>
                    <h5>{{ number_format($totalEntreeCDF, 2) }}</h5>
                </div>

                <div class="col-md-2">
                    <strong>Total Sortie CDF</strong>
                    <h5>{{ number_format($totalSortieCDF, 2) }}</h5>
                </div>

            </div>
        </div>
    </div>
    
    <!-- ACTIONS MODAL -->
@if($journal->statut === 'En attente' && (auth()->user()->can('valider', $journal) || auth()->user()->can('rejeter', $journal)))

<div class="card shadow-lg border-0 mt-4">

    <div class="card-header bg-dark text-white d-flex align-items-center">

        <i class="bi bi-shield-check fs-4 me-2"></i>

        <h5 class="mb-0">
            Traitement du journal comptable
        </h5>

    </div>


    <div class="card-body">


        <div class="alert alert-warning">

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

            Veuillez vérifier les informations avant validation ou rejet.

        </div>



        <div class="d-flex justify-content-end gap-2">


            <!-- MODAL -->

            <button type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalTraitementJournal">

                <i class="bi bi-check-circle me-1"></i>

                Traiter le journal

            </button>


        </div>


    </div>

</div>


<!-- MODAL -->

<div class="modal fade"
     id="modalTraitementJournal"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <form action="{{ route('journaux.valider',$journal->id) }}"
                  method="POST">

                @csrf


                <!-- HEADER -->
                <div class="modal-header bg-dark text-white">

                    <h5 class="modal-title">

                        <i class="bi bi-journal-check me-2"></i>

                        Traitement du journal

                    </h5>


                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal">
                    </button>


                </div>





                <!-- BODY -->
                <div class="modal-body">



                    <!-- Informations journal -->

                    <div class="alert alert-info">


                        <strong>Référence :</strong>

                        {{ $journal->reference }}


                        <br>


                        <strong>Type :</strong>

                        {{ $journal->type }}



                        <br>


                        <strong>Monnaie :</strong>

                        {{ $journal->monnaie }}



                    </div>






                    <!-- TYPE JOURNAL -->

                    <div class="mb-3">


                        <label class="form-label fw-bold">

                            Type de journal

                            <span class="text-danger">*</span>

                        </label>




                        <select name="journal_type_id"

                                class="form-select"

                                required>



                            <option value="">

                                -- Choisir --

                            </option>




                            @foreach($journalTypes as $journalType)


                                <option value="{{ $journalType->id }}"

                                {{ old('journal_type_id', $journal->journal_type_id) == $journalType->id ? 'selected' : '' }}>
                                {{ $journalType->libelle }}


                                </option>



                            @endforeach



                        </select>


                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Libellé
                            <span class="text-danger">*</span>
                        </label>
                        <select name="liste_des_comptes_id"
                                id="liste_des_comptes_id_modal"
                                class="form-select searchable-account"
                                required>
                            <option value="">-- Rechercher un libellé --</option>
                            @foreach($comptes as $compte)
                                <option value="{{ $compte->id }}"
                                    {{ old('liste_des_comptes_id', $journal->liste_des_comptes_id) == $compte->id ? 'selected' : '' }}>
                                    {{ $compte->designation }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>
                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <!-- REJET -->
                    @can('rejeter', $journal)
                        <button type="submit"
                                formaction="{{ route('journaux.rejeter',$journal->id) }}"
                                formnovalidate
                                class="btn btn-danger">
                            <i class="bi bi-x-circle"></i>
                            Rejeter
                        </button>
                    @endcan
                    <!-- VALIDATION -->
                    @can('valider', $journal)
                        <button type="submit"
                                class="btn btn-success">
                            <i class="bi bi-check-circle"></i>
                            Valider
                        </button>
                    @endcan
                </div>
            </form>
        </div>
    </div>
</div>
@else

<!-- JOURNAL DEJA TRAITE -->

<div class="card shadow-sm border-0 mt-4">


    <div class="card-body text-center">



        <i class="bi bi-check-circle-fill text-success"
           style="font-size:70px;"></i>




        <h5 class="mt-3">

            Ce journal a déjà été traité

        </h5>
        <p class="text-muted">
            Statut actuel :
            <strong>
                {{ $journal->statut }}
            </strong>
        </p>
        <a href="{{ route('journaux.index') }}"
           class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Retour
        </a>
        @can('reouvrir', $journal)
            @if($journal->statut !== 'En attente')
                <form method='POST' action='{{ route('journaux.reouvrir', $journal->id) }}' class='d-inline'>
                    @csrf
                    @method('PATCH')
                    <button class='btn btn-warning' onclick="return confirm('Remettre ce journal en attente ?')">
                        <i class='bi bi-arrow-counterclockwise'></i> Remettre en attente
                    </button>
                </form>
            @endif
        @endcan
        @can('rejeter', $journal)
            @if($journal->statut !== 'Rejeté')
                <form method="POST" action="{{ route('journaux.rejeter', $journal->id) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-danger" onclick="return confirm('Rejeter ce journal ?')">
                        <i class="bi bi-x-circle"></i> Rejeter
                    </button>
                </form>
            @endif
        @endcan
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(function () {
    const modal = $('#modalTraitementJournal');
    $('#liste_des_comptes_id_modal').select2({
        width: '100%',
        dropdownParent: modal,
        placeholder: 'Rechercher un libellé',
        allowClear: true,
        language: {
            noResults: function () { return 'Aucun libellé trouvé'; },
            searching: function () { return 'Recherche…'; }
        }
    });
});
</script>
@endpush
