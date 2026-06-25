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
    </div>

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
    
    <!-- INFORMATIONS COMPLÉMENTAIRES -->
    <div class="card shadow-sm">
        <div class="card-header"><strong>Informations Comptables</strong></div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-3">
                    <div class="p-3 border rounded bg-light h-100">
                        <strong>Pièce justificative</strong>
                        <div class="mt-2">{{ $journal->piece_justificatif ?? '-' }}</div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- ACTIONS MODAL -->
@if($journal->statut !== 'Validé')

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


            <!-- MODIFIER -->

            <a href="{{ route('journaux.edit',$journal->id) }}"
               class="btn btn-warning">

                <i class="bi bi-pencil-square me-1"></i>

                Modifier

            </a>




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




                            @foreach($journalTypes as $type)


                                <option value="{{ $type->id }}"

                                {{ $journal->journal_type_id == $type->id ? 'selected' : '' }}>


                                    {{ $type->compte->designation ?? 'Non défini' }}


                                </option>



                            @endforeach



                        </select>


                    </div>









                    <!-- MODE PAIEMENT -->

                    <div class="mb-3">


                        <label class="form-label fw-bold">

                            Mode de paiement

                            <span class="text-danger">*</span>

                        </label>




                        <select name="mode_paiement"

                                class="form-select"

                                required>



                            <option value="">
                                -- Choisir le mode --
                            </option>
                            <option value="Espèces"

                            {{ $journal->mode_paiement == 'Espèces' ? 'selected' : '' }}>
                                Espèces
                            </option>
                            <option value="Banque"

                            {{ $journal->mode_paiement == 'Banque' ? 'selected' : '' }}>
                                Banque
                            </option>
                            <option value="Mobile Money"

                            {{ $journal->mode_paiement == 'Mobile Money' ? 'selected' : '' }}>

                                Mobile Money

                            </option>
                            <option value="Chèque"
                            {{ $journal->mode_paiement == 'Chèque' ? 'selected' : '' }}>
                                Chèque
                            </option>
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
                    <button type="submit"
                            formaction="{{ route('journaux.rejeter',$journal->id) }}"
                            class="btn btn-danger">
                        <i class="bi bi-x-circle"></i>
                        Rejeter
                    </button>
                    <!-- VALIDATION -->
                    <button type="submit"
                            class="btn btn-success">
                        <i class="bi bi-check-circle"></i>
                        Valider
                    </button>
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
    </div>
</div>
@endif
@endsection