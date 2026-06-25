@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="card shadow border-0">

        {{-- HEADER --}}
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">

            <h4 class="mb-0">
                <i class="bi bi-journal-plus me-2"></i>
                Enregistrement d'un journal
            </h4>


            <div>

                <span class="fw-bold">
                    Taux du jour :
                </span>

                <span class="badge bg-warning text-dark fs-6">

                    {{ number_format($tauxDeChange->taux_de_change ?? 0,2,',',' ') }}

                </span>

            </div>

        </div>



        <div class="card-body">


            <form action="{{ route('journaux.store') }}" method="POST">

                @csrf



                {{-- TAUX DE CHANGE --}}

                <input type="hidden"
                       name="taux_de_change_id"
                       value="{{ $tauxDeChange->id ?? '' }}">


                <input type="hidden"
                       id="taux"
                       value="{{ $tauxDeChange->taux_de_change ?? 0 }}">





                {{-- INFORMATIONS JOURNAL --}}

                <div class="card border-primary mb-4">

                    <div class="card-header bg-light">

                        <strong>
                            Informations du journal
                        </strong>

                    </div>


                    <div class="card-body">


                        <div class="row g-3">



                            <div class="col-md-4">

                                <label class="form-label">
                                    Type journal
                                </label>


                                <select name="journal_type_id"
                                        class="form-select"
                                        required>


                                    <option value="">
                                        Sélectionner
                                    </option>


                                    @foreach($journalTypes as $type)

                                        <option value="{{ $type->id }}">

                                            {{ $type->compte->designation ?? '' }}

                                        </option>

                                    @endforeach


                                </select>

                            </div>





                            <div class="col-md-4">

                                <label class="form-label">
                                    Date
                                </label>


                                <input type="date"
                                       name="date"
                                       class="form-control"
                                       value="{{ date('Y-m-d') }}">

                            </div>





                            <div class="col-md-4">

                                <label class="form-label">
                                    Mode paiement
                                </label>


                                <select name="mode_paiement"
                                        class="form-select">


                                    <option value="Espèces">
                                        Espèces
                                    </option>


                                    <option value="Banque">
                                        Banque
                                    </option>


                                    <option value="Mobile Money">
                                        Mobile Money
                                    </option>


                                </select>

                            </div>





                            <div class="col-md-4">

                                <label class="form-label">
                                    Monnaie
                                </label>


                                <select name="monnaie"
                                        id="monnaie"
                                        class="form-select">


                                    <option value="CDF">
                                        CDF
                                    </option>


                                    <option value="USD">
                                        USD
                                    </option>


                                </select>


                            </div>



                        </div>


                    </div>


                </div>






                {{-- CLIENT --}}

                <div class="card border-success mb-4">


                    <div class="card-header bg-light">

                        <strong>
                            Informations client
                        </strong>

                    </div>



                    <div class="card-body">


                        <div class="row g-3">


                            <div class="col-md-6">

                                <label>
                                    Nom client
                                </label>


                                <input type="text"
                                       name="noms_client"
                                       class="form-control">

                            </div>



                            <div class="col-md-6">

                                <label>
                                    Téléphone
                                </label>


                                <input type="text"
                                       name="telephone"
                                       class="form-control">

                            </div>



                            <div class="col-md-12">

                                <label>
                                    Description
                                </label>


                                <textarea name="description"
                                          class="form-control"></textarea>

                            </div>


                        </div>


                    </div>


                </div>







                {{-- MONTANTS --}}

                <div class="card border-warning mb-4">


                    <div class="card-header bg-light">

                        <strong>
                            Montants
                        </strong>

                    </div>




                    <div class="card-body">


                        <div class="row g-3">



                            <div class="col-md-4">

                                <label>
                                    Entrées CDF
                                </label>


                                <input type="number"
                                       step="0.01"
                                       id="entrees_cdf"
                                       name="entrees_cdf"
                                       value="0"
                                       class="form-control">


                            </div>





                            <div class="col-md-4">

                                <label>
                                    Entrées USD
                                </label>


                                <input type="number"
                                       step="0.01"
                                       id="entrees_usd"
                                       name="entrees_usd"
                                       value="0"
                                       class="form-control">


                            </div>



                        </div>


                    </div>


                </div>







                {{-- TOTAL --}}

                <div class="card border-info mb-4">


                    <div class="card-header bg-light">

                        <strong>
                            Total
                        </strong>

                    </div>




                    <div class="card-body">


                        <label>
                            Total Entrées CDF
                        </label>



                        <input type="text"
                               id="total_entrees_cdf_label"
                               class="form-control fw-bold"
                               readonly
                               value="0">



                        <input type="hidden"
                               name="total_entrees_cdf"
                               id="total_entrees_cdf"
                               value="0">


                    </div>


                </div>






                {{-- BOUTONS --}}

                <div class="text-end">


                    <a href="{{ route('journaux.index') }}"
                       class="btn btn-secondary">

                        Retour

                    </a>



                    <button type="submit"
                            class="btn btn-success">

                        <i class="bi bi-save"></i>

                        Enregistrer

                    </button>



                </div>



            </form>


        </div>


    </div>


</div>


@endsection







@section('scripts')


<script>

document.addEventListener("DOMContentLoaded",function(){


    const cdf = document.getElementById('entrees_cdf');

    const usd = document.getElementById('entrees_usd');

    const monnaie = document.getElementById('monnaie');

    const taux = parseFloat(
        document.getElementById('taux').value
    ) || 0;


    const total = document.getElementById('total_entrees_cdf');

    const label = document.getElementById('total_entrees_cdf_label');




    function calcul(){


        let resultat = 0;


        if(monnaie.value === "USD"){

            resultat = (parseFloat(usd.value) || 0) * taux;

        }else{

            resultat = parseFloat(cdf.value) || 0;

        }



        total.value = resultat;


        label.value =
            new Intl.NumberFormat('fr-FR')
            .format(resultat)
            +" CDF";

    }




    cdf.addEventListener('input',calcul);

    usd.addEventListener('input',calcul);

    monnaie.addEventListener('change',calcul);



    calcul();


});


</script>


@endsection