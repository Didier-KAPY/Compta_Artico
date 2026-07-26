<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EcritureComptable;
use App\Models\ListeDesComptes;

class BalanceController extends Controller
{

    public function index(Request $request)
    {


        $dateDebut = $request->input(
            'date_debut',
            now()->startOfMonth()->toDateString()
        );


        $dateFin = $request->input(
            'date_fin',
            now()->toDateString()
        );


        /*
        |--------------------------------------------------------------------------
        | FILTRE CLASSE COMPTABLE
        |--------------------------------------------------------------------------
        */

        $classeRecherche = $request->input('classe');



        $comptes = ListeDesComptes::orderBy('compte')
            ->get();



        $balance = collect();



        foreach ($comptes as $compte) {



            /*
            |--------------------------------------------------------------------------
            | CALCUL AUTOMATIQUE DE LA CLASSE
            |--------------------------------------------------------------------------
            */


            $classeCompte = substr(
                $compte->compte,
                0,
                1
            );



            if (
                $classeRecherche &&
                $classeCompte != $classeRecherche
            ) {

                continue;

            }




            /*
            |--------------------------------------------------------------------------
            | SOLDE INITIAL AVANT PERIODE
            |--------------------------------------------------------------------------
            */


            $initial = EcritureComptable::where(
                    'liste_des_comptes_id',
                    $compte->id
                )
                ->where(
                    'statut',
                    'Validé'
                )
                ->whereDate(
                    'date',
                    '<',
                    $dateDebut
                )
                ->selectRaw("
                    COALESCE(SUM(debit_cdf),0) AS debit,
                    COALESCE(SUM(credit_cdf),0) AS credit
                ")
                ->first();





            /*
            |--------------------------------------------------------------------------
            | MOUVEMENTS DE LA PERIODE
            |--------------------------------------------------------------------------
            */


            $mouvement = EcritureComptable::where(
                    'liste_des_comptes_id',
                    $compte->id
                )
                ->where(
                    'statut',
                    'Validé'
                )
                ->whereBetween(
                    'date',
                    [
                        $dateDebut,
                        $dateFin
                    ]
                )
                ->selectRaw("
                    COALESCE(SUM(debit_cdf),0) AS debit,
                    COALESCE(SUM(credit_cdf),0) AS credit
                ")
                ->first();





            $initialDebit = $initial->debit ?? 0;

            $initialCredit = $initial->credit ?? 0;


            $mouvementDebit = $mouvement->debit ?? 0;

            $mouvementCredit = $mouvement->credit ?? 0;





            $soldeInitialDebiteur = 0;

            $soldeInitialCrediteur = 0;


            $soldeFinalDebiteur = 0;

            $soldeFinalCrediteur = 0;





            /*
            |--------------------------------------------------------------------------
            | CALCUL DES SOLDES SELON LA NATURE
            |--------------------------------------------------------------------------
            */


            if (
                in_array(
                    $compte->nature,
                    [
                        'Actif',
                        'Charge'
                    ]
                )
            ) {



                $soldeInitial =
                    $initialDebit - $initialCredit;



                if ($soldeInitial >= 0) {


                    $soldeInitialDebiteur =
                        $soldeInitial;


                } else {


                    $soldeInitialCrediteur =
                        abs($soldeInitial);


                }





                $soldeFinal =
                    (
                        $initialDebit + $mouvementDebit
                    )
                    -
                    (
                        $initialCredit + $mouvementCredit
                    );




                if ($soldeFinal >= 0) {


                    $soldeFinalDebiteur =
                        $soldeFinal;


                } else {


                    $soldeFinalCrediteur =
                        abs($soldeFinal);


                }



            } else {



                $soldeInitial =
                    $initialCredit - $initialDebit;



                if ($soldeInitial >= 0) {


                    $soldeInitialCrediteur =
                        $soldeInitial;


                } else {


                    $soldeInitialDebiteur =
                        abs($soldeInitial);


                }





                $soldeFinal =
                    (
                        $initialCredit + $mouvementCredit
                    )
                    -
                    (
                        $initialDebit + $mouvementDebit
                    );




                if ($soldeFinal >= 0) {


                    $soldeFinalCrediteur =
                        $soldeFinal;


                } else {


                    $soldeFinalDebiteur =
                        abs($soldeFinal);


                }



            }





            /*
            |--------------------------------------------------------------------------
            | NE PAS AFFICHER LES COMPTES VIDES
            |--------------------------------------------------------------------------
            */


            if (

                $initialDebit == 0 &&
                $initialCredit == 0 &&
                $mouvementDebit == 0 &&
                $mouvementCredit == 0 &&
                $soldeFinalDebiteur == 0 &&
                $soldeFinalCrediteur == 0

            ) {


                continue;


            }





            /*
            |--------------------------------------------------------------------------
            | ENVOI DANS LA BALANCE
            |--------------------------------------------------------------------------
            */


            $balance->push([


                'id' => $compte->id,


                'compte' => $compte->compte,


                'designation' => $compte->designation,


                'nature' => $compte->nature,



                'initial_debit' =>
                    $soldeInitialDebiteur,


                'initial_credit' =>
                    $soldeInitialCrediteur,



                'mouvement_debit' =>
                    $mouvementDebit,


                'mouvement_credit' =>
                    $mouvementCredit,



                'final_debit' =>
                    $soldeFinalDebiteur,


                'final_credit' =>
                    $soldeFinalCrediteur,



            ]);



        }





        return view(
            'comptabilite.balance.index',
            compact(
                'balance',
                'dateDebut',
                'dateFin',
                'classeRecherche'
            )
        );


    }

}