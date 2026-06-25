@extends('layouts.app')

@section('content')

<div class="container py-4">


    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h4 class="mb-0">
                <i class="bi bi-plus-circle me-2"></i>
                Nouveau compte
            </h4>

            <small class="text-muted">
                Ajouter un compte comptable
            </small>

        </div>


        <a href="{{ route('parametres.comptes') }}"
           class="btn btn-secondary btn-sm">

            <i class="bi bi-arrow-left"></i>
            Retour

        </a>

    </div>




    <div class="card shadow-sm border-0">


        <div class="card-header bg-dark text-white">

            <i class="bi bi-journal-bookmark"></i>

            Informations du compte

        </div>




        <div class="card-body">


            @if(session('success'))

                <div class="alert alert-success">
                    {{ session('success') }}
                </div>

            @endif




            <form action="{{ route('parametres.comptes.store') }}"
                  method="POST">


                @csrf



                <div class="row">



                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-bold">
                            Numéro compte
                        </label>


                        <input type="text"
                               name="compte"
                               class="form-control"
                               placeholder="Ex: 411000"
                               required>

                    </div>





                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-bold">
                            Désignation
                        </label>


                        <input type="text"
                               name="designation"
                               class="form-control"
                               placeholder="Nom du compte"
                               required>

                    </div>






                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-bold">
                            Nature
                        </label>


                        <select name="nature"
                                class="form-select"
                                required>


                            <option value="">
                                -- Choisir --
                            </option>
                            <option value="Débiteur">
                                Débiteur
                            </option>
                            <option value="Créditeur">
                                Créditeur
                            </option>
                            <option value="Charge">
                                Charge
                            </option>
                            <option value="Produit">
                                Produit
                            </option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">

                        <label class="form-label fw-bold">
                            Observation
                        </label>


                        <textarea name="observation"
                                  class="form-control"
                                  rows="3"></textarea>


                    </div>



                </div>






                <div class="text-end">


                    <button class="btn btn-success">

                        <i class="bi bi-save me-1"></i>

                        Enregistrer

                    </button>


                </div>



            </form>



        </div>


    </div>


</div>


@endsection