@extends('layouts.app')

@section('content')
<!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-0">
                <i class="bi bi-gear-fill me-2"></i>
                Paramètres
            </h4>

            <small class="text-muted">
                Configuration générale du système
            </small>
        </div>
        <a href="{{ route('parametres.parametre') }}"
           class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>
            Retour
        </a>
    </div>

    <!-- CADRE ENTREPRISE -->
    <div class="card shadow-sm border-0 mb-4">


        <div class="card-header bg-dark text-white">

            <i class="bi bi-building me-2"></i>

            Informations de l'entreprise

        </div>



        <div class="card-body">


            @if(session('success'))

                <div class="alert alert-success">

                    <i class="bi bi-check-circle me-2"></i>

                    {{ session('success') }}

                </div>

            @endif





            <form action="{{ route('parametres.update') }}"
                  method="POST"
                  enctype="multipart/form-data">


                @csrf



                <!-- LOGO -->

                <div class="text-center mb-4">


                    @if($entreprise?->logo)

                        <img src="{{ asset('storage/'.$entreprise->logo) }}"
                             class="rounded-circle shadow"
                             style="width:120px;height:120px;object-fit:cover;">


                    @else

                        <i class="bi bi-building"
                           style="font-size:100px;">
                        </i>

                    @endif



                    <div class="mt-3">

                        <label class="btn btn-outline-primary btn-sm">

                            <i class="bi bi-camera"></i>

                            Modifier logo


                            <input type="file"
                                   name="logo"
                                   hidden>

                        </label>

                    </div>


                </div>



                <hr>



                <div class="row g-3">



                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            Nom entreprise
                        </label>
                        <input type="text"
                               name="nom_entreprise"
                               class="form-control"
                               value="{{ old('nom_entreprise',$entreprise->nom_entreprise ?? '') }}"
                               required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            Forme juridique
                        </label>
                        <input type="text"
                               name="forme_juridique"
                               class="form-control"
                               value="{{ old('forme_juridique',$entreprise->forme_juridique ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            Numéro fiscal (NIF)
                        </label>
                        <input type="text"
                               name="numero_identification_fiscal"
                               class="form-control"
                               value="{{ old('numero_identification_fiscal',$entreprise->numero_identification_fiscal ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            Téléphone
                        </label>
                        <input type="text"
                               name="telephone"
                               class="form-control"
                               value="{{ old('telephone',$entreprise->telephone ?? '') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">
                            Adresse
                        </label>
                        <textarea name="adresse"
                                  class="form-control"
                                  rows="2">{{ old('adresse',$entreprise->adresse ?? '') }}</textarea>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button class="btn btn-success">
                        <i class="bi bi-save me-1"></i>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection