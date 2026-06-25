@extends('layouts.app')

@section('content')

<div class="container py-4">


    {{-- MESSAGE CREATION --}}
    @if(session('success'))

        <div class="alert alert-success">

            <i class="bi bi-check-circle me-2"></i>

            {{ session('success') }}

        </div>


        <div class="alert alert-warning">


            <h6>
                <i class="bi bi-key me-2"></i>
                Informations de connexion
            </h6>


            <hr>


            <strong>Email :</strong>

            {{ session('agent_email') }}


            <br><br>


            <strong>Mot de passe par défaut :</strong>


            <span class="badge bg-danger fs-6">

                {{ session('password_default') }}

            </span>



            <br><br>


            <small class="text-muted">

                L'utilisateur devra modifier ce mot de passe à sa première connexion.

            </small>


        </div>


    @endif






    {{-- HEADER --}}

    <div class="d-flex justify-content-between align-items-center mb-4">


        <div>


            <h4 class="mb-0">

                <i class="bi bi-person-plus me-2"></i>

                Nouvel utilisateur

            </h4>


            <small class="text-muted">

                Création d'un nouvel agent

            </small>


        </div>





        <a href="{{ route('profil.index') }}"
           class="btn btn-secondary btn-sm">


            <i class="bi bi-arrow-left me-1"></i>

            Retour


        </a>


    </div>







    {{-- CARD FORMULAIRE --}}

    <div class="card shadow-sm border-0">



        <div class="card-header bg-dark text-white">


            <i class="bi bi-person-plus me-2"></i>

            Informations agent


        </div>





        <div class="card-body">



            <form action="{{ route('profil.user.store') }}"
                  method="POST"
                  enctype="multipart/form-data">


                @csrf




                <div class="row">






                    {{-- NOM --}}

                    <div class="col-md-6 mb-3">


                        <label class="form-label fw-bold">

                            Nom

                        </label>


                        <input type="text"
                               name="nom"
                               class="form-control"
                               required>


                    </div>








                    {{-- PRENOM --}}

                    <div class="col-md-6 mb-3">


                        <label class="form-label fw-bold">

                            Prénom

                        </label>


                        <input type="text"
                               name="prenom"
                               class="form-control"
                               required>


                    </div>









                    {{-- EMAIL --}}

                    <div class="col-md-6 mb-3">


                        <label class="form-label fw-bold">

                            Email

                        </label>


                        <input type="email"
                               name="email"
                               class="form-control"
                               required>


                    </div>









                    {{-- TELEPHONE --}}

                    <div class="col-md-6 mb-3">


                        <label class="form-label fw-bold">

                            Téléphone

                        </label>


                        <input type="text"
                               name="telephone"
                               class="form-control"
                               required>


                    </div>










                    {{-- ADRESSE --}}

                    <div class="col-md-12 mb-3">


                        <label class="form-label fw-bold">

                            Adresse

                        </label>



                        <textarea name="adresse"
                                  class="form-control"
                                  rows="2"></textarea>


                    </div>











                    {{-- ROLE --}}

                    <div class="col-md-6 mb-3">


                        <label class="form-label fw-bold">

                            Rôle

                        </label>



                        <select name="role_id"
                                class="form-select"
                                required>


                            <option value="">

                                -- Choisir un rôle --

                            </option>




                            @foreach($roles as $role)


                                <option value="{{ $role->id }}">


                                    {{ $role->designation }}


                                    @if($role->type)

                                        - {{ $role->type }}

                                    @endif


                                </option>


                            @endforeach



                        </select>


                    </div>












                    {{-- PHOTO --}}

                    <div class="col-md-6 mb-3">


                        <label class="form-label fw-bold">

                            Photo

                        </label>


                        <input type="file"
                               name="photo"
                               class="form-control">


                    </div>









                    {{-- STATUT --}}

                    <div class="col-md-6 mb-3">


                        <label class="form-label fw-bold">

                            Statut

                        </label>



                        <select name="statut"
                                class="form-select">



                            <option value="Actif">

                                Actif

                            </option>



                            <option value="Inactif">

                                Inactif

                            </option>



                        </select>


                    </div>




                </div>








                <div class="alert alert-info">


                    <i class="bi bi-info-circle me-2"></i>


                    Le mot de passe sera généré automatiquement.
                    L'utilisateur devra le modifier à sa première connexion.


                </div>








                <div class="text-end">


                    <button type="submit"
                            class="btn btn-success">


                        <i class="bi bi-save me-1"></i>


                        Créer l'agent


                    </button>


                </div>





            </form>




        </div>


    </div>



</div>


@endsection