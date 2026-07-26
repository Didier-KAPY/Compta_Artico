<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EcritureComptable;
use Illuminate\Support\Facades\DB;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class EcritureComptableController extends Controller
{
    /**
 * Liste des écritures comptables
 */
public function liste(Request $request)
{
    $query = EcritureComptable::with([
        'journal',
        'compte',
        'user'
    ]);

    // Si aucune date n'est saisie, afficher uniquement les écritures du jour
    if (!$request->filled('date_debut') && !$request->filled('date_fin')) {

        $query->whereDate('date', today());

    } else {

        if ($request->filled('date_debut')) {
            $query->whereDate('date', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date', '<=', $request->date_fin);
        }
    }

    $ecritures = $query
        ->orderBy('date', 'desc')
        ->paginate(20)
        ->withQueryString();

    // Totaux CDF
    $totalDebitCDF = (clone $query)->sum('debit_cdf');
    $totalCreditCDF = (clone $query)->sum('credit_cdf');

    return view(
        'comptabilite.ecritures.liste',
        compact(
            'ecritures',
            'totalDebitCDF',
            'totalCreditCDF',
        )
    );
}

    /**
     * Validation d'une écriture
     */
    public function valider($id)
{
    $ecriture = EcritureComptable::findOrFail($id);


    // Vérifier si déjà validée
    if (trim($ecriture->statut) === 'Validé') {

        return back()->with(
            'warning',
            'Cette écriture est déjà validée.'
        );

    }


    $ecriture->statut = 'Validé';
    $ecriture->date_validation = now();
    $ecriture->valide_par = Auth::id();

    $ecriture->save();


    return back()->with(
        'success',
        'Écriture validée avec succès.'
    );
}
    /**
     * Formulaire de modification
     */
    public function edit($id)
    {
        $ecriture = EcritureComptable::findOrFail($id);

        return view(
            'comptabilite.ecritures.modifier',
            compact('ecriture')
        );
    }

    /**
     * Mise à jour
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'libelle' => 'required|string|max:255',
            'debit_cdf' => 'nullable|numeric',
            'credit_cdf' => 'nullable|numeric',
            'debit_usd' => 'nullable|numeric',
            'credit_usd' => 'nullable|numeric',
        ]);

        $ecriture = EcritureComptable::findOrFail($id);

        $ecriture->update($request->all());

        return redirect()
            ->route('ecritures.liste')
            ->with(
                'success',
                'Écriture modifiée avec succès.'
            );
    }

    /**
     * Suppression
     */
    public function destroy($id)
    {
        $ecriture = EcritureComptable::findOrFail($id);

        $ecriture->delete();

        return back()->with(
            'success',
            'Écriture supprimée avec succès.'
        );
    }
   public function brc(Request $request)
{

    $dateDebut = $request->input(
        'date_debut',
        now()->toDateString()
    );


    $dateFin = $request->input(
        'date_fin',
        now()->toDateString()
    );



    $ecritures = EcritureComptable::with([
        'compte',
        'journal'
    ])
    ->where('statut','Validé')
    ->whereBetween('date',[
        $dateDebut,
        $dateFin
    ])
    ->get();




    /*
    |--------------------------------------------------------------------------
    | REGROUPEMENT PAR COMPTE
    |--------------------------------------------------------------------------
    */


    $brc = $ecritures
        ->groupBy('liste_des_comptes_id')
        ->map(function($lignes){


            return [

                'date' =>
                    $lignes->first()->date,


                'reference' =>
                    $lignes->first()->journal->reference ?? '-',


                'compte' =>
                    $lignes->first()->compte->compte ?? '-',


                'designation' =>
                    $lignes->first()->compte->designation ?? '-',



                'debit' =>
                    $lignes->sum('debit_cdf'),



                'credit' =>
                    $lignes->sum('credit_cdf'),

            ];


        });



    $totalDebit = $brc->sum('debit');


    $totalCredit = $brc->sum('credit');




    /*
    |--------------------------------------------------------------------------
    | NUMERO BRC
    |--------------------------------------------------------------------------
    */


    $numeroBrc = 'BRC-' .
        now()->format('Ymd') .
        '-' .
        str_pad(
            EcritureComptable::whereDate(
                'created_at',
                now()->toDateString()
            )->count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );




    /*
    |--------------------------------------------------------------------------
    | ENTREPRISE
    |--------------------------------------------------------------------------
    */


    $entreprise = \App\Models\Entreprise::first();




    return view(
        'comptabilite.ecritures.brc',
        compact(
            'brc',
            'totalDebit',
            'totalCredit',
            'dateDebut',
            'dateFin',
            'numeroBrc',
            'entreprise'
        )
    );

}
public function formBrc()
{

    return view(
        'comptabilite.ecritures.form_brc'
    );

}
public function genererBrc(Request $request)
{


    $request->validate([

        'date_debut'=>'required|date',

        'date_fin'=>'required|date',

    ]);



    $dateDebut = $request->date_debut;


    $dateFin = $request->date_fin;




    $ecritures = EcritureComptable::with([
        'compte',
        'journal'
    ])
    ->where('statut','Validé')
    ->whereBetween(
        'date',
        [
            $dateDebut,
            $dateFin
        ]
    )
    ->get();



    $brc = $ecritures
        ->groupBy('liste_des_comptes_id')
        ->map(function($ligne){


            return [

                'date'=>$ligne->first()->date,

                'reference'=>$ligne->first()->journal->reference ?? '-',

                'compte'=>$ligne->first()->compte->compte ?? '-',

                'designation'=>$ligne->first()->compte->designation ?? '-',

                'debit'=>$ligne->sum('debit_cdf'),

                'credit'=>$ligne->sum('credit_cdf'),

            ];


        });



    $totalDebit = $brc->sum('debit');


    $totalCredit = $brc->sum('credit');



    $numeroBrc =
        'BRC-'.
        now()->format('Ymd').
        '-0001';



    $entreprise = \App\Models\Entreprise::first();



    return view(
        'comptabilite.ecritures.brc',
        compact(
            'brc',
            'totalDebit',
            'totalCredit',
            'dateDebut',
            'dateFin',
            'numeroBrc',
            'entreprise'
        )
    );

}
public function brcPdf(Request $request)
{

    $dateDebut = $request->date_debut;

    $dateFin = $request->date_fin;



    $ecritures = EcritureComptable::with([
        'compte',
        'journal'
    ])
    ->where('statut','Validé')
    ->whereBetween(
        'date',
        [
            $dateDebut,
            $dateFin
        ]
    )
    ->get();




    $brc = $ecritures
        ->groupBy('liste_des_comptes_id')
        ->map(function($lignes){


            return [

                'date'=>$lignes->first()->date,

                'reference'=>$lignes->first()->journal->reference ?? '-',

                'compte'=>$lignes->first()->compte->compte ?? '-',

                'designation'=>$lignes->first()->compte->designation ?? '-',


                'debit'=>$lignes->sum('debit_cdf'),

                'credit'=>$lignes->sum('credit_cdf'),

            ];


        })
        ->values();



    $totalDebit = $brc->sum('debit');

    $totalCredit = $brc->sum('credit');



    $numeroBrc =
        'BRC-'
        .now()->format('Ymd')
        .'-0001';



    $entreprise = \App\Models\Entreprise::first();



    $pdf = Pdf::loadView(
        'comptabilite.ecritures.brc_pdf',
        compact(
            'brc',
            'totalDebit',
            'totalCredit',
            'dateDebut',
            'dateFin',
            'numeroBrc',
            'entreprise'
        )
    )
    ->setPaper('a4','portrait');



    return $pdf->download(
        $numeroBrc.'.pdf'
    );

}
 /**
     * Formulaire nouvelle écriture/imputation
     */
    public function create()
{

    // Journaux hors trésorerie pour la saisie
    $journaux = JournalType::with('compte')
        ->where('est_tresorerie', false)
        ->orderBy('code')
        ->get();


    // Tous les journaux pour l'affichage du tableau
    $journalTypes = JournalType::with('compte')
        ->orderBy('code')
        ->get();


    $comptes = ListeDesComptes::orderBy('compte')
        ->get();



    return view(
        'Comptabilite.ecritures.create',
        compact(
            'journaux',
            'journalTypes',
            'comptes'
        )
    );

}
    /**
     * Enregistrement écriture/imputation
     */
   public function store(Request $request)
{

    $request->validate([

        'date' => 'required|date',

        'journal_type_id' => 'required|exists:journal_types,id',

        'sens' => 'required|in:debit,credit',

        'lignes' => 'required|array|min:1',

        'lignes.*.compte_id' => 'required|exists:liste_des_comptes,id',

        'lignes.*.montant' => 'required|numeric|min:0.01',

    ]);



    DB::transaction(function () use ($request) {


        /*
        |--------------------------------------------------------------------------
        | Récupération du journal type
        |--------------------------------------------------------------------------
        */


        $journalType = JournalType::with('compte')
            ->findOrFail($request->journal_type_id);



        if(!$journalType->compte){

            throw new \Exception(
                "Ce journal n'a pas de compte associé."
            );

        }





        /*
        |--------------------------------------------------------------------------
        | Calcul du montant total
        |--------------------------------------------------------------------------
        */


        $total = 0;


        foreach($request->lignes as $ligne){

            $total += floatval($ligne['montant']);

        }



        if($total <= 0){

            throw new \Exception(
                "Le montant doit être supérieur à zéro."
            );

        }





        /*
        |--------------------------------------------------------------------------
        | Génération référence journal
        |--------------------------------------------------------------------------
        */


        $reference = 'OD-' . date('YmdHis');






        /*
        |--------------------------------------------------------------------------
        | Création du journal
        |--------------------------------------------------------------------------
        */


        $journal = Journaux::create([


            'user_id'=>auth()->id(),


            'journal_type_id'=>$journalType->id,


            'liste_des_comptes_id'=>$journalType->compte->id,


            'reference'=>$reference,


            'date'=>$request->date,


            'description'=>$request->description 
                ?? 'Opération diverse',



            /*
             | ENUM :
             | recette
             | depense
             | achat
             | vente
             | od
             */

            'type'=>'od',



            'monnaie'=>'CDF',


            'montant_ht'=>$total,


            'montant_ttc'=>$total,


            'entrees_cdf'=>0,


            'sorties_cdf'=>0,



            'statut'=>'Validé',


            'date_validation'=>now(),


            'valide_par'=>auth()->id(),


        ]);








        /*
        |--------------------------------------------------------------------------
        | Création contrepartie
        |--------------------------------------------------------------------------
        */


        EcritureComptable::create([


            'user_id'=>auth()->id(),


            'journal_id'=>$journal->id,


            'liste_des_comptes_id'=>$journalType->compte->id,


            'date'=>$request->date,


            'libelle'=>$request->description 
                ?? 'Contrepartie',




            'debit_cdf'=> $request->sens == 'credit'
                ? $total
                : 0,



            'credit_cdf'=> $request->sens == 'debit'
                ? $total
                : 0,



            'statut'=>'Validé',


            'date_validation'=>now(),


            'valide_par'=>auth()->id(),


        ]);










        /*
        |--------------------------------------------------------------------------
        | Création lignes d'imputation
        |--------------------------------------------------------------------------
        */


        foreach($request->lignes as $ligne){



            if($ligne['compte_id'] == $journalType->compte->id){


                throw new \Exception(

                    "Le compte du journal ne peut pas être utilisé comme imputation."

                );

            }





            EcritureComptable::create([



                'user_id'=>auth()->id(),



                'journal_id'=>$journal->id,



                'liste_des_comptes_id'=>$ligne['compte_id'],



                'date'=>$request->date,



                'libelle'=>$ligne['libelle']
                    ?? 'Imputation comptable',




                'debit_cdf'=> $request->sens == 'debit'
                    ? $ligne['montant']
                    : 0,



                'credit_cdf'=> $request->sens == 'credit'
                    ? $ligne['montant']
                    : 0,



                'statut'=>'Validé',



                'date_validation'=>now(),



                'valide_par'=>auth()->id(),


            ]);

        }



    });





    return redirect()

        ->route('ecritures.create')

        ->with(

            'success',

            'Écriture comptable enregistrée avec succès.'

        );

}
}