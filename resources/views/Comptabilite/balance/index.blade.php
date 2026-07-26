@extends('layouts.app')

@section('content')


<div class="card shadow-sm border-0 mt-3">


    <div class="card-header bg-dark text-white d-flex align-items-center">

        <i class="bi bi-calculator me-2"></i>

        <strong>
            Balance générale
        </strong>

    </div>




    <div class="card-body">



        {{-- FILTRE --}}


        <form method="GET"
              class="row g-2 mb-3">



            <div class="col-md-3">


                <label class="form-label">

                    Date début

                </label>


                <input type="date"
                       name="date_debut"
                       class="form-control"
                       value="{{ $dateDebut }}">


            </div>





            <div class="col-md-3">


                <label class="form-label">

                    Date fin

                </label>


                <input type="date"
                       name="date_fin"
                       class="form-control"
                       value="{{ $dateFin }}">


            </div>






            <div class="col-md-3">


                <label class="form-label">

                    Classe comptable

                </label>



                <select name="classe"
                        class="form-select">



                    <option value="">

                        Toutes les classes

                    </option>



                    <option value="1"
                    {{ $classeRecherche == '1' ? 'selected' : '' }}>

                        Classe 1 - Capitaux

                    </option>




                    <option value="2"
                    {{ $classeRecherche == '2' ? 'selected' : '' }}>

                        Classe 2 - Immobilisations

                    </option>




                    <option value="3"
                    {{ $classeRecherche == '3' ? 'selected' : '' }}>

                        Classe 3 - Stocks

                    </option>




                    <option value="4"
                    {{ $classeRecherche == '4' ? 'selected' : '' }}>

                        Classe 4 - Tiers

                    </option>




                    <option value="5"
                    {{ $classeRecherche == '5' ? 'selected' : '' }}>

                        Classe 5 - Trésorerie

                    </option>




                    <option value="6"
                    {{ $classeRecherche == '6' ? 'selected' : '' }}>

                        Classe 6 - Charges

                    </option>




                    <option value="7"
                    {{ $classeRecherche == '7' ? 'selected' : '' }}>

                        Classe 7 - Produits

                    </option>



                </select>


            </div>






            <div class="col-md-2 d-flex align-items-end">


                <button class="btn btn-primary w-100">


                    <i class="bi bi-search"></i>

                    Afficher


                </button>


            </div>



        </form>







        <div class="table-responsive">


            <table class="table table-bordered table-sm align-middle">



                <thead class="table-dark">



                    <tr>


                        <th rowspan="2"
                            class="text-center">

                            Compte

                        </th>


                        <th rowspan="2">

                            Désignation

                        </th>



                        <th colspan="2"
                            class="text-center">

                            Solde initial

                        </th>



                        <th colspan="2"
                            class="text-center">

                            Mouvements

                        </th>



                        <th colspan="2"
                            class="text-center">

                            Solde final

                        </th>


                    </tr>





                    <tr>


                        <th>

                            Débiteur

                        </th>


                        <th>

                            Créditeur

                        </th>


                        <th>

                            Débit

                        </th>


                        <th>

                            Crédit

                        </th>


                        <th>

                            Débiteur

                        </th>


                        <th>

                            Créditeur

                        </th>


                    </tr>



                </thead>






                <tbody>



                @php


                    $totalInitialDebit = 0;

                    $totalInitialCredit = 0;


                    $totalMouvementDebit = 0;

                    $totalMouvementCredit = 0;


                    $totalFinalDebit = 0;

                    $totalFinalCredit = 0;



                @endphp





                @forelse($balance as $ligne)




                    @php


                    $totalInitialDebit += $ligne['initial_debit'];

                    $totalInitialCredit += $ligne['initial_credit'];


                    $totalMouvementDebit += $ligne['mouvement_debit'];

                    $totalMouvementCredit += $ligne['mouvement_credit'];


                    $totalFinalDebit += $ligne['final_debit'];

                    $totalFinalCredit += $ligne['final_credit'];



                    @endphp





                    <tr>




                        <td class="text-center fw-bold">


                            <a href="{{ route('grandlivre.index',[

                                'compte'=>$ligne['compte'],

                                'date_debut'=>$dateDebut,

                                'date_fin'=>$dateFin

                            ]) }}">


                                {{ $ligne['compte'] }}


                            </a>


                        </td>






                        <td>


                            {{ $ligne['designation'] }}


                        </td>






                        <td class="text-end">

                            {{ number_format($ligne['initial_debit'],2,',',' ') }}

                        </td>




                        <td class="text-end">

                            {{ number_format($ligne['initial_credit'],2,',',' ') }}

                        </td>





                        <td class="text-end">

                            {{ number_format($ligne['mouvement_debit'],2,',',' ') }}

                        </td>




                        <td class="text-end">

                            {{ number_format($ligne['mouvement_credit'],2,',',' ') }}

                        </td>





                        <td class="text-end fw-bold">

                            {{ number_format($ligne['final_debit'],2,',',' ') }}

                        </td>





                        <td class="text-end fw-bold">

                            {{ number_format($ligne['final_credit'],2,',',' ') }}

                        </td>



                    </tr>





                @empty



                    <tr>

                        <td colspan="8"
                            class="text-center text-muted">

                            Aucun mouvement comptable trouvé

                        </td>

                    </tr>



                @endforelse




                </tbody>






                <tfoot>


                    <tr class="table-dark fw-bold">


                        <td colspan="2"
                            class="text-center">

                            TOTAUX GENERAUX

                        </td>



                        <td class="text-end">

                            {{ number_format($totalInitialDebit,2,',',' ') }}

                        </td>


                        <td class="text-end">

                            {{ number_format($totalInitialCredit,2,',',' ') }}

                        </td>


                        <td class="text-end">

                            {{ number_format($totalMouvementDebit,2,',',' ') }}

                        </td>


                        <td class="text-end">

                            {{ number_format($totalMouvementCredit,2,',',' ') }}

                        </td>


                        <td class="text-end">

                            {{ number_format($totalFinalDebit,2,',',' ') }}

                        </td>


                        <td class="text-end">

                            {{ number_format($totalFinalCredit,2,',',' ') }}

                        </td>



                    </tr>


                </tfoot>




            </table>


        </div>





        <button onclick="window.print()"
                class="btn btn-secondary mt-3">


            <i class="bi bi-printer"></i>

            Imprimer


        </button>




    </div>


</div>





<style>


.table th {

    text-align:center;

    vertical-align:middle;

}


.table td {

    white-space:nowrap;

}



@media print {


    form,
    button,
    .card-header {

        display:none !important;

    }


}



</style>



@endsection