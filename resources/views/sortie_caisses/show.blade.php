@extends('layouts.app')

@section('content')

<div class="container py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-cash-stack me-2"></i>
                Bon de Sortie 
            </h4>
            <small class="text-muted">
                Dossier financier / validation caisse
            </small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('sortie-caisses.index') }}"
               class="btn btn-secondary btn-sm">
                Retour
            </a>

            <a href="#"
               class="btn btn-outline-dark btn-sm">
                Imprimer
            </a>
        </div>
    </div>

    <!-- STATUT -->
    <div class="alert
        @if($sortie->statut == 'Validé')
            alert-success
        @elseif($sortie->statut == 'Rejeté')
            alert-danger
        @else
            alert-warning
        @endif
        d-flex justify-content-between align-items-center">

        <div>
            <strong>Statut :</strong>
            {{ $sortie->statut ?? 'En attente' }}
        </div>

        <div>
            <strong>Numéro :</strong>
            {{ $sortie->numero }}
        </div>
    </div>

    <!-- INFORMATIONS GENERALES -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white">
            Informations générales
        </div>

        <div class="card-body">
            <div class="row">

                <div class="col-md-3">
                    <strong>Date</strong><br>
                    {{ $sortie->date }}
                </div>

                <div class="col-md-3">
                    <strong>Bénéficiaire</strong><br>
                    {{ $sortie->beneficiaire }}
                </div>

                <div class="col-md-3">
                    <strong>Créé par</strong><br>
                    {{ $sortie->user->nom ?? '-' }}
                </div>

                <div class="col-md-3">
                    <strong>Monnaie</strong><br>
                    {{ $sortie->monnaie }}
                </div>

            </div>

            <hr>

            <div class="row">

                <div class="col-md-8">
                    <strong>Motif</strong><br>
                    {{ $sortie->motif }}
                </div>

                <div class="col-md-4 text-end">
                    <strong>Montant</strong>
                    <h4 class="text-primary">
                        {{ number_format($sortie->montant, 2) }}
                    </h4>
                </div>

            </div>
        </div>
    </div>
    <!-- DETAIL  -->
    @if($sortie->etatBesoin && $sortie->etatBesoin->lignes->count())
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-dark text-white">
                <i class="bi bi-list-ul me-2"></i>
                Détails de l'état de besoin
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Désignation</th>
                                <th>Quantité</th>
                                <th>Prix unitaire</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sortie->etatBesoin->lignes as $ligne)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $ligne->designation }}</td>
                                <td>{{ $ligne->quantite }}</td>
                                <td>
                                    {{ number_format($ligne->prix_unitaire, 2) }}
                                </td>
                                <td>
                                    {{ number_format($ligne->montant, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">
                                    Total
                                </th>
                                <th>
                                    {{ number_format($sortie->etatBesoin->lignes->sum('montant'), 2) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif
    <!-- OBSERVATION -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white">
            Observation
        </div>

        <div class="card-body">
            {{ $sortie->observation ?? 'Aucune observation' }}
        </div>
    </div>

    <!-- ETAT DE BESOIN -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white">
            État de besoin associé
        </div>

        <div class="card-body d-flex justify-content-between align-items-center">

            <div>
                <strong>État de besoin lié :</strong>
            </div>

            <div>
                @if($sortie->etat_besoin_id)

                    <a href="{{ route('etat-besoins.show', $sortie->etat_besoin_id) }}"
                       class="btn btn-outline-primary btn-sm">

                        <i class="bi bi-eye"></i>
                        Voir l'état de besoin

                    </a>

                @else

                    <span class="text-muted">
                        Aucun état de besoin associé
                    </span>

                @endif
            </div>

        </div>
    </div>

    @if($sortie->statut == 'En attente')

        <!-- TRAITEMENT -->
        <div class="card shadow-lg border-0 mt-4">

            <div class="card-header bg-dark text-white d-flex align-items-center">
                <i class="bi bi-shield-check fs-4 me-2"></i>

                <h5 class="mb-0">
                    Traitement du bon de sortie
                </h5>
            </div>

            <div class="card-body">

                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Une décision est obligatoire avant validation ou rejet.
                </div>

                <div class="d-flex justify-content-end gap-2">

                    <a href="{{ route('sortie-caisses.edit', $sortie->id) }}"
                       class="btn btn-warning">

                        <i class="bi bi-pencil-square me-1"></i>
                        Modifier

                    </a>

                    <button type="button"
                            class="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#modalTraitement">

                        <i class="bi bi-check-circle me-1"></i>
                        Traiter le bon de sortie

                    </button>

                </div>

            </div>
        </div>

        <!-- MODAL -->
        <div class="modal fade"
             id="modalTraitement"
             tabindex="-1">

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    <form action="{{ route('sortie-caisses.valider', $sortie->id) }}"
                          method="POST">

                        @csrf

                        <div class="modal-header bg-dark text-white">

                            <h5 class="modal-title">
                                <i class="bi bi-cash-stack me-2"></i>
                                Traitement du bon de sortie
                            </h5>

                            <button type="button"
                                    class="btn-close btn-close-white"
                                    data-bs-dismiss="modal">
                            </button>

                        </div>

                        <div class="modal-body">

                            <div class="alert alert-info">

                                <strong>Numéro :</strong>
                                {{ $sortie->numero }}

                                <br>

                                <strong>Montant :</strong>
                                {{ number_format($sortie->montant, 2) }}
                                {{ $sortie->monnaie }}

                                <br>

                                <strong>Bénéficiaire :</strong>
                                {{ $sortie->beneficiaire }}

                            </div>

                            <div class="mb-3">

                                <label class="form-label fw-bold">
                                    Observation
                                </label>

                                <textarea name="observation"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Observation de validation...">{{ $sortie->observation }}</textarea>

                            </div>

                        </div>

                        <div class="modal-footer">

                            <button type="button"
                                    class="btn btn-secondary"
                                    data-bs-dismiss="modal">
                                Annuler
                            </button>

                            <button type="submit"
                                    formaction="{{ route('sortie-caisses.rejeter', $sortie->id) }}"
                                    class="btn btn-danger">

                                <i class="bi bi-x-circle"></i>
                                Rejeter

                            </button>

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

        <!-- DEJA TRAITE -->
        <div class="card shadow-sm border-0 mt-4">

            <div class="card-body text-center">

                @if($sortie->statut == 'Validé')

                    <i class="bi bi-check-circle-fill text-success"
                       style="font-size:70px;"></i>

                @else

                    <i class="bi bi-x-circle-fill text-danger"
                       style="font-size:70px;"></i>

                @endif

                <h5 class="mt-3">
                    Ce bon de sortie a déjà été traité
                </h5>

                <p class="text-muted">
                    Statut actuel :
                    <strong>{{ $sortie->statut }}</strong>
                </p>

                <a href="{{ route('sortie-caisses.index') }}"
                   class="btn btn-secondary">

                    <i class="bi bi-arrow-left"></i>
                    Retour

                </a>

            </div>

        </div>

    @endif

</div>

@endsection