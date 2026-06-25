@extends('layouts.app')

@section('content')

<div class="container py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">
                <i class="bi bi-cash-coin me-2"></i>
                Détail du bon d'entrée
            </h4>

            <small class="text-muted">
                Consultation et traitement de l'entrée de caisse
            </small>
        </div>

        <a href="{{ route('entree-caisses.index') }}"
           class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Retour
        </a>

    </div>



    <!-- INFORMATIONS GENERALES -->

    <div class="card shadow-sm border-0 mb-3">

        <div class="card-header bg-dark text-white">
            <i class="bi bi-info-circle me-2"></i>
            Informations générales
        </div>


        <div class="card-body">

            <div class="row text-center g-3">


                <div class="col-md-3">
                    <strong>Référence</strong>
                    <br>
                    {{ $entree->numero }}
                </div>


                <div class="col-md-3">
                    <strong>Date</strong>
                    <br>
                    {{ $entree->date }}
                </div>


                <div class="col-md-3">
                    <strong>Type</strong>
                    <br>
                    {{ $entree->type }}
                </div>


                <div class="col-md-3">

                    <strong>Statut</strong>
                    <br>

                    <span class="badge
                    @if($entree->statut=='Validé')
                    bg-success
                    @elseif($entree->statut=='Rejeté')
                    bg-danger
                    @else
                    bg-warning
                    @endif">

                    {{ $entree->statut }}

                    </span>

                </div>


            </div>


        </div>

    </div>





    <!-- MOTIF -->

    <div class="card shadow-sm border-0 mb-3">


        <div class="card-header">
            <strong>
                <i class="bi bi-card-text me-2"></i>
                Motif
            </strong>
        </div>


        <div class="card-body">

            {{ $entree->motif }}

        </div>


    </div>







    <!-- DETAILS -->

    <div class="card shadow-sm border-0 mb-3">


        <div class="card-header">

            <strong>
                Détails de l'entrée
            </strong>

        </div>


        <div class="card-body table-responsive">


            <table class="table table-bordered align-middle">


                <thead class="table-dark">

                <tr>

                    <th>Désignation</th>
                    <th>Quantité</th>
                    <th>Prix</th>
                    <th>Montant</th>

                </tr>

                </thead>



                <tbody>


                @foreach($entree->lignes as $ligne)


                <tr>

                    <td>{{ $ligne->designation }}</td>

                    <td>
                        {{ $ligne->quantite }}
                    </td>

                    <td>
                        {{ number_format($ligne->prix_unitaire,2) }}
                    </td>

                    <td>
                        {{ number_format($ligne->montant,2) }}
                    </td>


                </tr>


                @endforeach


                </tbody>


            </table>


        </div>


    </div>
    <!-- TOTAL -->

    <div class="alert alert-primary text-end">

        <h5 class="mb-0">

            Total :

            <strong>

            {{ number_format($entree->montant,2) }}

            {{ $entree->monnaie }}

            </strong>

        </h5>

    </div>
@if($entree->statut == 'En attente')

<div class="card shadow-lg border-0 mt-4">
    <div class="card-header bg-dark text-white">
        <i class="bi bi-shield-check me-2"></i>
        Traitement de l'entrée de caisse
    </div>

    <div class="card-body">
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Une observation est obligatoire avant validation ou rejet.
        </div>

        <div class="text-end">
            <button class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalTraitement{{ $entree->id }}">
                <i class="bi bi-check-circle me-1"></i>
                Traiter l'entrée
            </button>
        </div>
    </div>
</div>

<!-- MODAL TRAITEMENT -->
<div class="modal fade" id="modalTraitement{{ $entree->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form method="POST"
                  action="{{ route('entree-caisses.valider',$entree->id) }}">
                @csrf

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-stack me-2"></i>
                        Traitement entrée de caisse
                    </h5>

                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">

                    <div class="alert alert-info">
                        <strong>Référence :</strong> {{ $entree->numero }}<br>
                        <strong>Montant :</strong>
                        {{ number_format($entree->montant,2) }}
                        {{ $entree->monnaie }}<br>

                        <strong>Motif :</strong>
                        {{ $entree->motif }}
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Observation
                            <span class="text-danger">*</span>
                        </label>

                        <textarea name="observation"
                                  class="form-control"
                                  rows="4"
                                  required
                                  placeholder="Saisir votre observation...">{{ old('observation',$entree->observation) }}</textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Annuler
                    </button>

                    <button type="submit"
                            name="action"
                            value="rejeter"
                            formaction="{{ route('entree-caisses.rejeter',$entree->id) }}"
                            class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>
                        Rejeter
                    </button>

                    <button type="submit"
                            name="action"
                            value="valider"
                            class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Valider
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

@else

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body text-center py-5">

        @if($entree->statut == 'Validé')
            <i class="bi bi-check-circle-fill text-success"
               style="font-size:70px"></i>
        @else
            <i class="bi bi-x-circle-fill text-danger"
               style="font-size:70px"></i>
        @endif

        <h5 class="mt-3">
            Cette entrée a déjà été traitée
        </h5>

        <p class="text-muted">
            Statut actuel :
            <strong>{{ $entree->statut }}</strong>
        </p>

        <a href="{{ route('entree-caisses.index') }}"
           class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Retour
        </a>

    </div>
</div>

@endif

@endsection