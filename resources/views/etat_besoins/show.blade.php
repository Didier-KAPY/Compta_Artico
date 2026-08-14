@extends('layouts.app')

@section('content')

@php
    $role = strtolower(auth()->user()->role?->designation ?? '');
    $estGestionnaire = auth()->user()->isSuperAdmin();
    $validationSeulement = auth()->user()->isManagement();
    $bonSortieValide = $etat->sortieCaisses->contains('statut', 'Validé');
@endphp


<div class="container py-4">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}</div>
    @endif

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h4 class="mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>
                Détails État de Besoin
            </h4>

            <small class="text-muted">
                Consultation et validation financière
            </small>

        </div>


        <div class="d-flex align-items-center gap-2">
            @can('deleteFinancialDocument')
                <button type="button"
                        class="btn btn-danger btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#modalSuppressionDocument">
                    <i class="bi bi-trash me-1"></i>
                    Supprimer
                </button>
            @endcan

            <a href="{{ route('etat-besoins.index') }}"
               class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>
                Retour
            </a>
        </div>

    </div>

    @include('partials.document-navigation')

    @include('partials.financial-delete-modal', [
        'documentType' => 'État de besoin', 'documentReference' => $etat->numero,
        'documentStatus' => $etat->statut, 'deleteRoute' => route('etat-besoins.destroy', $etat),
    ])





    <!-- INFORMATIONS -->

    <div class="card shadow-sm border-0 mb-3">

        <div class="card-body">


            <div class="row g-3">


                <div class="col-md-3">

                    <strong>Numéro</strong><br>

                    {{ $etat->numero }}

                </div>



                <div class="col-md-3">

                    <strong>Date</strong><br>

                    {{ \Carbon\Carbon::parse($etat->date)->format('d/m/Y') }}

                </div>




                <div class="col-md-3">

                    <strong>Département</strong><br>

                    {{ $etat->departement?->designation ?? $etat->service }}

                </div>




                <div class="col-md-3">

                    <strong>Demandeur</strong><br>

                    {{ $etat->demandeur }}

                </div>




                <div class="col-md-3">

                    <strong>Statut</strong><br>


                    @if($etat->statut != 'En attente')

                        <span class="badge bg-success">
                            Validé
                        </span>


                    @elseif($etat->statut == 'Rejeté')


                        <span class="badge bg-danger">
                            Rejeté
                        </span>


                    @else


                        <span class="badge bg-warning text-dark">
                            En attente
                        </span>


                    @endif


                </div>





                <div class="col-md-3">

                    <strong>Monnaie</strong><br>

                    <span class="badge bg-secondary">

                        {{ $etat->monnaie }}

                    </span>

                </div>





                <div class="col-md-6">

                    <strong>Motif</strong><br>

                    {{ $etat->motif }}

                </div>



            </div>




            <hr>

            @if($etat->ligneBudgetaire)
                <div class="alert alert-info border-0">
                    <strong><i class="bi bi-wallet2 me-1"></i> Ligne budgétaire :</strong>
                    {{ $etat->ligneBudgetaire->code }} — {{ $etat->ligneBudgetaire->rubrique }}
                    <span class="float-md-end">Disponible : {{ number_format($etat->ligneBudgetaire->disponible, 2, ',', ' ') }} {{ $etat->ligneBudgetaire->budget->monnaie }}</span>
                </div>
            @endif




            <div class="text-end">


                <h5 class="text-primary">


                    Total :

                    {{ number_format($etat->montant_estime,2) }}


                </h5>


            </div>



        </div>

    </div>







    <!-- LIGNES -->


    <div class="card shadow-sm border-0">


        <div class="card-header bg-dark text-white">


            <i class="bi bi-list-check me-2"></i>

            Lignes du besoin


        </div>




        <div class="card-body p-0">


            <table class="table table-bordered mb-0">


                <thead class="table-light">


                    <tr>

                        <th>Désignation</th>
                        <th width="120">Quantité</th>
                        <th>Prix unitaire</th>
                        <th>Montant</th>

                    </tr>


                </thead>




                <tbody>


                @forelse($etat->lignes as $ligne)


                    <tr>


                        <td>
                            {{ $ligne->designation }}
                        </td>


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


                @empty


                    <tr>

                        <td colspan="4"
                            class="text-center text-muted">

                            Aucune ligne

                        </td>


                    </tr>


                @endforelse


                </tbody>


            </table>


        </div>


    </div>






    <!-- TRAITEMENT -->

<!-- TRAITEMENT -->

@if(
    !$bonSortieValide
    &&
    ($etat->statut == 'En attente'
    ||
    (
        in_array($etat->statut, ['Validé', 'Rejeté'])
        &&
        $estGestionnaire
    )
    )
)


<div class="card shadow-lg border-0 mt-4">


    <div class="card-header bg-dark text-white">

        <i class="bi bi-shield-check me-2"></i>

        Traitement de l'état de besoin

    </div>



    <div class="card-body">


        @if($etat->statut == 'Validé')

            <div class="alert alert-success">

                <i class="bi bi-check-circle-fill me-2"></i>

                Cet état de besoin est déjà validé.
                Vous pouvez le remettre en attente.

            </div>


        @else

            <div class="alert alert-warning">

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                Une observation est obligatoire avant validation ou rejet.

            </div>


        @endif




        <div class="text-end">


            @can('manageEtatBesoin')
            @if($etat->statut == 'En attente' || $estGestionnaire)
                <a href="{{ route('etat-besoins.edit',$etat->id) }}"
                   class="btn btn-warning">

                    <i class="bi bi-pencil-square me-1"></i>

                    Modifier

                </a>
            @endif
            @endcan

            <button class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalTraitement">


                <i class="bi bi-check-circle me-1"></i>

                Traiter


            </button>


        </div>


    </div>


</div>





<!-- MODAL TRAITEMENT -->

<div class="modal fade"
     id="modalTraitement"
     tabindex="-1">


    <div class="modal-dialog modal-dialog-centered">


        <div class="modal-content">



            <form action="{{ route('etat-besoins.valider',$etat->id) }}"
                  method="POST">

                @csrf



                <div class="modal-header bg-dark text-white">


                    <h5 class="modal-title">

                        <i class="bi bi-file-earmark-check me-2"></i>

                        Traitement état de besoin

                    </h5>



                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal">

                    </button>


                </div>





                <div class="modal-body">


                    <div class="alert alert-info">


                        <strong>Numéro :</strong>

                        {{ $etat->numero }}

                        <br>


                        <strong>Montant :</strong>

                        {{ number_format($etat->montant_estime,2) }}

                        {{ $etat->monnaie }}


                    </div>

                        


                    <label class="form-label fw-bold">

                        Observation

                        <span class="text-danger">*</span>

                    </label>



                    <textarea name="observation"
                              class="form-control"
                              rows="4"
                              required>{{ old('observation',$etat->observation) }}</textarea>


                    <input type="hidden"
                           name="monnaie"
                           value="{{ $etat->monnaie }}">



                </div>





                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Annuler
                    </button>

                    @unless($validationSeulement)
                    <button type="submit"
                            name="action"
                            value="rejeter"
                            class="btn btn-danger">

                        <i class="bi bi-x-circle me-1"></i>
                        Rejeter

                    </button>
                    @endunless

                    @if($etat->statut == 'Validé')

                        @unless($validationSeulement)
                        <button type="submit"
                                name="action"
                                value="attente"
                                class="btn btn-warning">

                            <i class="bi bi-arrow-counterclockwise me-1"></i>
                            Remettre en attente

                        </button>
                        @endunless

                    @else

                        <button type="submit"
                                name="action"
                                value="valider"
                                class="btn btn-success">

                            <i class="bi bi-check-circle me-1"></i>
                            Valider

                        </button>

                    @endif

                </div>



            </form>


        </div>


    </div>


</div>




@else



<div class="card shadow-sm border-0 mt-4">


    <div class="card-body text-center">


        @if($etat->statut == 'Validé')

            <i class="bi bi-check-circle-fill text-success"
               style="font-size:70px;"></i>


        @elseif($etat->statut == 'Rejeté')


            <i class="bi bi-x-circle-fill text-danger"
               style="font-size:70px;"></i>


        @endif




        <h5 class="mt-3">

            {{ $bonSortieValide ? 'Action verrouillée par le bon de sortie validé' : 'Cet état de besoin a déjà été traité' }}

        </h5>




        <p class="text-muted">

            {{ $bonSortieValide ? 'Remettez d’abord le bon de sortie en attente. Statut actuel :' : 'Statut actuel :' }}

            <strong>
                {{ $etat->statut }}
            </strong>


        </p>




        <a href="{{ route('etat-besoins.index') }}"
           class="btn btn-secondary">

            <i class="bi bi-arrow-left me-1"></i>

            Retour

        </a>



    </div>


</div>



@endif

@endsection
