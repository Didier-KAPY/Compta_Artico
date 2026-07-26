<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;
use App\Models\Journaux;
use App\Models\ParametrageComptable;
use App\Models\JournalType;
use App\Models\EcritureComptable;
use App\Models\ListeDesComptes;
use App\Models\TauxDeChange;


class JournalController extends Controller
{


    public function index(Request $request)
{
    $journaux = Journaux::with('journalType')

        ->when($request->date_debut, function($query) use($request){

            $query->whereDate(
                'date',
                '>=',
                $request->date_debut
            );

        })

        ->when($request->date_fin, function($query) use($request){

            $query->whereDate(
                'date',
                '<=',
                $request->date_fin
            );

        })

        ->orderBy('date','desc')

        ->paginate(20);


    return view(
        'journaux.index',
        compact('journaux')
    );
}

public function create()
{

    /*
    |--------------------------------------------------------------------------
    | JOURNAUX DE TRESORERIE
    |--------------------------------------------------------------------------
    */

    $journalTypes = JournalType::with('compte')

        ->where('user_id', Auth::id())

        ->where('est_tresorerie', true)

        ->whereNotNull('liste_des_comptes_id')

        ->orderBy('id')

        ->get();



    /*
    |--------------------------------------------------------------------------
    | COMPTES D'OPERATION
    |--------------------------------------------------------------------------
    */

    $comptes = ListeDesComptes::where(
            'user_id',
            Auth::id()
        )

        ->orderBy('compte')

        ->get();



    /*
    |--------------------------------------------------------------------------
    | TAUX DE CHANGE
    |--------------------------------------------------------------------------
    */

    $taux = TauxDeChange::latest()->first();



    return view(
        'Journaux.create',
        compact(
            'journalTypes',
            'comptes',
            'taux'
        )
    );

}

  public function store(Request $request)
{

    $request->validate([

        'journal_type_id'=>'required|exists:journal_types,id',

        'liste_des_comptes_id'=>'required|exists:liste_des_comptes,id',

        'date'=>'required|date',

        'type'=>'required|in:recette,depense,achat,vente,od',

        'montant_ttc'=>'required|numeric|min:0.01',

        'monnaie'=>'required|in:CDF,USD',

        'mode_paiement'=>'required',

        'piece_justificatif'=>'nullable|file',

    ]);



    DB::transaction(function() use($request){



        /*
        |--------------------------------------------------------------------------
        | JOURNAL
        |--------------------------------------------------------------------------
        */


        $journalType = JournalType::with('compte')
            ->where('user_id',Auth::id())
            ->findOrFail($request->journal_type_id);



        if(!$journalType->compte){

            throw new \Exception(
                "Le journal n'a pas de compte trésorerie."
            );

        }



        $compteTresorerie = $journalType->compte;



        /*
        |--------------------------------------------------------------------------
        | COMPTE OPERATION
        |--------------------------------------------------------------------------
        */


        $compteOperation = ListeDesComptes::where(
            'user_id',
            Auth::id()
        )
        ->findOrFail($request->liste_des_comptes_id);




        /*
        |--------------------------------------------------------------------------
        | MONTANT
        |--------------------------------------------------------------------------
        */


        $montantUSD = 0;

        $montantCDF = 0;



        if($request->monnaie == "USD"){


            $montantUSD = $request->montant_ttc;


            $taux = TauxDeChange::latest()->first();


            $montantCDF = 
                $montantUSD * ($taux->taux_de_change ?? 1);


        }
        else{

            $montantCDF = $request->montant_ttc;

        }





        /*
        |--------------------------------------------------------------------------
        | TVA
        |--------------------------------------------------------------------------
        */


        $tauxTVA = $request->taux_tva ?? 0;


        $montantHT = $montantCDF;


        $montantTVA = 0;



        if($tauxTVA > 0){


            $montantHT =
            $montantCDF /
            (1 + ($tauxTVA/100));


            $montantTVA =
            $montantCDF -
            $montantHT;


        }





        /*
        |--------------------------------------------------------------------------
        | REFERENCE
        |--------------------------------------------------------------------------
        */


        $reference =
            "ART-".
            date('Ymd').
            "-".
            str_pad(
                Journaux::count()+1,
                5,
                '0',
                STR_PAD_LEFT
            );





        /*
        |--------------------------------------------------------------------------
        | PIECE
        |--------------------------------------------------------------------------
        */


        $piece = null;


        if($request->hasFile('piece_justificatif')){

            $piece =
            $request->file('piece_justificatif')
            ->store('pieces','public');

        }





        /*
        |--------------------------------------------------------------------------
        | CREATION JOURNAL
        |--------------------------------------------------------------------------
        */


        $journal = Journaux::create([


            'user_id'=>Auth::id(),


            'journal_type_id'=>$journalType->id,


            // IMPORTANT : uniquement trésorerie

            'liste_des_comptes_id'=>$compteTresorerie->id,


            'reference'=>$reference,


            'date'=>$request->date,


            'nom_partenaire'=>$request->nom_partenaire,


            'telephone_partenaire'=>$request->telephone_partenaire,


            'adresse_partenaire'=>$request->adresse_partenaire,


            'piece_justificatif'=>$piece,


            'description'=>$request->description,


            'type'=>$request->type,


            'monnaie'=>$request->monnaie,


            'mode_paiement'=>$request->mode_paiement,


            'montant_ht'=>$montantHT,


            'taux_tva'=>$tauxTVA,


            'montant_tva'=>$montantTVA,


            'montant_ttc'=>$montantCDF,


            'statut'=>'Validé',


            'date_validation'=>now(),


            'valide_par'=>Auth::id(),


        ]);






        /*
        |--------------------------------------------------------------------------
        | ECRITURES COMPTABLES
        |--------------------------------------------------------------------------
        */


        if($request->type=="recette"){


            // DEBIT CAISSE/BANQUE


            EcritureComptable::create([


                'user_id'=>Auth::id(),

                'journal_id'=>$journal->id,

                'liste_des_comptes_id'=>$compteTresorerie->id,

                'date'=>$request->date,

                'piece'=>$reference,

                'libelle'=>$request->description,


                'debit_cdf'=>$montantCDF,

                'credit_cdf'=>0,


                'debit_usd'=>$montantUSD,

                'credit_usd'=>0,


                'statut'=>'Validé',

                'date_validation'=>now(),

                'valide_par'=>Auth::id(),

            ]);





            // CREDIT COMPTE PRODUIT


            EcritureComptable::create([


                'user_id'=>Auth::id(),

                'journal_id'=>$journal->id,

                'liste_des_comptes_id'=>$compteOperation->id,

                'date'=>$request->date,

                'piece'=>$reference,

                'libelle'=>$request->description,


                'debit_cdf'=>0,

                'credit_cdf'=>$montantHT,


                'debit_usd'=>0,

                'credit_usd'=>$montantUSD,


                'statut'=>'Validé',

                'date_validation'=>now(),

                'valide_par'=>Auth::id(),


            ]);

        }





        if($request->type=="depense"){


            // DEBIT CHARGE


            EcritureComptable::create([


                'user_id'=>Auth::id(),

                'journal_id'=>$journal->id,

                'liste_des_comptes_id'=>$compteOperation->id,


                'date'=>$request->date,

                'piece'=>$reference,

                'libelle'=>$request->description,


                'debit_cdf'=>$montantHT,

                'credit_cdf'=>0,


                'debit_usd'=>$montantUSD,

                'credit_usd'=>0,


                'statut'=>'Validé',

                'date_validation'=>now(),

                'valide_par'=>Auth::id(),


            ]);





            // CREDIT TRESORERIE


            EcritureComptable::create([


                'user_id'=>Auth::id(),

                'journal_id'=>$journal->id,

                'liste_des_comptes_id'=>$compteTresorerie->id,


                'date'=>$request->date,

                'piece'=>$reference,

                'libelle'=>$request->description,


                'debit_cdf'=>0,

                'credit_cdf'=>$montantCDF,


                'debit_usd'=>0,

                'credit_usd'=>$montantUSD,


                'statut'=>'Validé',

                'date_validation'=>now(),

                'valide_par'=>Auth::id(),

            ]);


        }



    });



    return redirect()

    ->route('journaux.index')

    ->with(
        'success',
        'Journal enregistré avec succès.'
    );

}

    private function getCompteTresorerie($mode)
    {


        return match($mode)
        {


            "espece" =>
                ListeDesComptes::where(
                    'compte',
                    'like',
                    '571%'
                )->first(),


            "banque" =>
                ListeDesComptes::where(
                    'compte',
                    'like',
                    '521%'
                )->first(),


            "mobile_money" =>
                ListeDesComptes::where(
                    'compte',
                    'like',
                    '53%'
                )->first(),


            default=>null

        };


    }
public function show($id)
{
    // Journal avec ses relations
    $journal = Journaux::with([
        'user',
        'journalType.compte',
        'entreeCaisse'
    ])->findOrFail($id);


    // Dernier taux de change
    $tauxActuel = TauxDeChange::latest()->first();


    // Lignes de l'entrée de caisse
    if ($journal->entree_caisse_id) {

        $lignes = EntreeCaisseLigne::where(
            'entree_caisse_id',
            $journal->entree_caisse_id
        )->get();

    } else {

        $lignes = collect();

    }


    // Détermination de la nature de l'opération
    if (
        $journal->entrees_cdf > 0 ||
        $journal->entrees_usd > 0
    ) {

        $nature = 'Entrée';

    } elseif (
        $journal->sorties_cdf > 0 ||
        $journal->sorties_usd > 0
    ) {

        $nature = 'Sortie';

    } else {

        $nature = null;

    }


    // Totaux
    $totalEntreeCDF = $journal->entrees_cdf ?? 0;
    $totalSortieCDF = $journal->sorties_cdf ?? 0;

    $totalEntreeUSD = $journal->entrees_usd ?? 0;
    $totalSortieUSD = $journal->sorties_usd ?? 0;


    // Types de journaux uniquement de trésorerie
    $journalTypes = JournalType::with('compte')
        ->where('est_tresorerie', true)
        ->get();


    return view('Journaux.show', compact(
        'journal',
        'tauxActuel',
        'lignes',
        'nature',
        'totalEntreeCDF',
        'totalSortieCDF',
        'totalEntreeUSD',
        'totalSortieUSD',
        'journalTypes'
    ));
}
public function rejeter(Request $request, $id)
{
    $journal = Journaux::findOrFail($id);

    $journal->update([
        'statut' => 'rejete', // ou 'Rejeté' selon les valeurs utilisées dans votre base
        'journal_type_id' => $request->journal_type_id,
        'mode_paiement' => $request->mode_paiement,
        'date_validation' => Carbon::now(),
        'valide_par' => Auth::id(),
    ]);

    return redirect()
        ->route('journaux.show', $journal->id)
        ->with('success', 'Le journal a été rejeté avec succès.');
}
public function valider($id)
{
    $journal = Journaux::findOrFail($id);

    $journal->update([
        'statut' => 'Validé',
        'date_validation' => now(),
        'valide_par' => auth()->id(),
    ]);

    return redirect()
        ->back()
        ->with('success', 'Journal validé avec succès.');
}

}