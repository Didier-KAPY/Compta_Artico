<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Journaux;
use App\Models\TauxDeChange;
use App\Models\JournalType;
use App\Models\ListeDesComptes;
use Illuminate\Support\Facades\DB;

class JournalController extends Controller
{
    public function index(Request $request)
{
    $query = Journaux::with([
        'user',
        'journalType.compte',
        'tauxDeChange'
    ]);

    // Afficher uniquement les données du jour si aucun filtre de date n'est fourni
    if (
        !$request->filled('date') &&
        !$request->filled('date_debut') &&
        !$request->filled('date_fin')
    ) {
        $query->whereDate('date', today());
    }

    // Filtre par référence
    if ($request->filled('reference')) {
        $query->where('reference', 'like', '%' . $request->reference . '%');
    }

    // Filtre par date précise
    if ($request->filled('date')) {
        $query->whereDate('date', $request->date);
    }

    // Filtre par intervalle de dates
    if ($request->filled('date_debut') && $request->filled('date_fin')) {

        $query->whereBetween('date', [
            $request->date_debut,
            $request->date_fin
        ]);

    } else {

        if ($request->filled('date_debut')) {
            $query->whereDate('date', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date', '<=', $request->date_fin);
        }
    }

    $journaux = $query->latest('id')->paginate(20);

    return view('journaux.index', compact('journaux'));
}

   public function show($id)
{

    $journal = Journaux::with([
        'user',
        'journalType.compte',
        'tauxDeChange',
        'entreeCaisse.lignes',
        'sortieCaisse.etatBesoin.lignes'
    ])->findOrFail($id);



    $tauxActuel = TauxDeChange::latest()->first();

    $taux = $tauxActuel->taux_de_change ?? 0;



    $totalEntreeCDF = 0;
    $totalSortieCDF = 0;



    if($journal->monnaie == 'CDF'){

        $totalEntreeCDF = $journal->entrees_cdf;

        $totalSortieCDF = $journal->sorties_cdf;

    }else{

        $totalEntreeCDF =
        ($journal->entrees_usd ?? 0) * $taux;


        $totalSortieCDF =
        ($journal->sorties_usd ?? 0) * $taux;

    }




    $type = auth()->user()->role?->type ?? 'caisse';


    $journalTypes = JournalType::with('compte')
    ->whereHas('compte', function($q) use($type){

        $q->where(
            'designation',
            'like',
            "%$type%"
        );

    })->get();





    $lignes = collect();

    $nature = null;



    // ENTREE

    if($journal->entreeCaisse)
    {

        $lignes =
        $journal->entreeCaisse->lignes;


        $nature = 'Entree';

    }



    // SORTIE

    elseif($journal->sortieCaisse)
    {

        if($journal->sortieCaisse->etatBesoin)
        {

            $lignes =
            $journal
            ->sortieCaisse
            ->etatBesoin
            ->lignes;


            $nature = 'Sortie';

        }

    }
    return view(
        'journaux.show',
        compact(
            'journal',
            'tauxActuel',
            'journalTypes',
            'totalEntreeCDF',
            'totalSortieCDF',
            'lignes',
            'nature'
        )
    );
}
 public function valider(Request $request, $id)
{
    $journal = Journaux::findOrFail($id);
    $taux = TauxDeChange::latest()->first();
    // Validation des champs reçus
    if ($journal->statut !== 'Validé') {
        $request->validate([
            'journal_type_id' => 'required|exists:journal_types,id',
            'mode_paiement' => 'required|string|max:50',
        ]);
    }
    // Toujours mettre à jour le mode de paiement
    if($request->filled('mode_paiement')) {
        $journal->mode_paiement = $request->mode_paiement;
    }
    if ($journal->statut === 'Validé') {
        // ==========================
        // REMETTRE EN ATTENTE
        // ==========================
        $journal->statut = 'En attente';
        $journal->date_validation = null;
        $journal->valide_par = null;
    } else {
        // ==========================
        // VALIDATION
        // ==========================
        $journal->statut = 'Validé';
        $journal->date_validation = now();
        $journal->valide_par = auth()->id();
        // TAUX DE CHANGE
        $journal->taux_de_change_id = $taux?->id;
        // TYPE JOURNAL
        if($request->filled('journal_type_id')) {
            $journalType = JournalType::find(
                $request->journal_type_id
            );
            if ($journalType) {
                $journal->journal_type_id =
                    $journalType->id;
                $journal->liste_des_comptes_id =
                    $journalType->liste_des_comptes_id;
            }
        }
        // ==========================
        // CONVERSION MONNAIE
        // ==========================
        if ($journal->monnaie === 'USD') {
            $journal->total_entrees_cdf =
                ($journal->entrees_usd ?? 0)
                *
                ($taux->taux_de_change ?? 0);
            $journal->total_sorties_cdf =
                ($journal->sorties_usd ?? 0)
                *
                ($taux->taux_de_change ?? 0);
        } else {
            $journal->total_entrees_cdf =
                $journal->entrees_cdf;
            $journal->total_sorties_cdf =
                $journal->sorties_cdf;
        }
    }
    $journal->save();
    return back()->with(
        'success',
        'Journal mis à jour avec succès.'
    );
}

    public function rejeter($id)
    {
        $journal = Journaux::findOrFail($id);

        if ($journal->statut === 'Rejeté') {
            $journal->statut = 'En attente';
        } else {
            $journal->statut = 'Rejeté';
        }

        $journal->save();

        return back()->with('success', 'Statut mis à jour.');
    }
    public function caisse(Request $request)
{
    $taux = TauxDeChange::latest()->first();
    $tauxValue = $taux->taux_de_change ?? 0;


    // =========================
    // ROLE DE L'UTILISATEUR CONNECTÉ
    // =========================
    $roleType = auth()->user()
        ->role
        ->type ?? null;



    // =========================
    // REQUÊTE JOURNAUX
    // =========================
    $query = Journaux::with([
        'user.role'
    ])
    ->where('statut', 'Validé')
    ->whereHas('user.role', function ($q) use ($roleType) {

        $q->where('type', $roleType);

    });



    // =========================
    // FILTRE DATE PAR DÉFAUT
    // =========================
    if (!$request->filled('date_debut') && !$request->filled('date_fin')) {

        $query->whereDate('date', today());

    }



    // =========================
    // FILTRE PÉRIODE
    // =========================
    if ($request->filled('date_debut')) {

        $query->whereDate(
            'date',
            '>=',
            $request->date_debut
        );

    }


    if ($request->filled('date_fin')) {

        $query->whereDate(
            'date',
            '<=',
            $request->date_fin
        );

    }
    // =========================
    // PAGINATION
    // =========================
    $journaux = $query
        ->orderBy('date', 'desc')
        ->paginate(15)
        ->appends($request->all());

    // =========================
    // TOTALS SUR TOUTES LES DONNÉES FILTRÉES
    // =========================
    $allJournaux = (clone $query)->get();
    $totaux = [
        'entrees_cdf' => $allJournaux->sum('entrees_cdf'),
        'sorties_cdf' => $allJournaux->sum('sorties_cdf'),
        'entrees_usd' => $allJournaux->sum('entrees_usd'),
        'sorties_usd' => $allJournaux->sum('sorties_usd'),

    ];
    // =========================
    // CONVERSION USD EN CDF
    // =========================

    $conversion = [

        'entrees_usd_cdf' => $allJournaux->sum(function($j) use ($tauxValue){

            return ($j->entrees_usd ?? 0) * $tauxValue;

        }),

        'sorties_usd_cdf' => $allJournaux->sum(function($j) use ($tauxValue){

            return ($j->sorties_usd ?? 0) * $tauxValue;

        }),

    ];
    $totaux['total_entrees_cdf'] =
        $totaux['entrees_cdf']
        +
        $conversion['entrees_usd_cdf'];



    $totaux['total_sorties_cdf'] =
        $totaux['sorties_cdf']
        +
        $conversion['sorties_usd_cdf'];





    // =========================
    // SOLDES
    // =========================

    $soldes = [

        'cdf' =>
            $totaux['entrees_cdf']
            -
            $totaux['sorties_cdf'],


        'usd' =>
            $totaux['entrees_usd']
            -
            $totaux['sorties_usd'],


        'usd_cdf' =>
            $totaux['total_entrees_cdf']
            -
            $totaux['total_sorties_cdf'],

    ];
    return view(
        'journaux.caisse',
        compact(
            'journaux',
            'totaux',
            'soldes',
            'taux'
        )
    );
}

   public function banque(Request $request)
{
    $taux = TauxDeChange::latest()->first();
    $tauxValue = $taux->taux_de_change ?? 0;


    // =========================
    // ROLE UTILISATEUR CONNECTÉ
    // =========================
    $roleType = auth()->user()?->role?->type;



    // =========================
    // REQUÊTE BANQUE
    // =========================
    $query = Journaux::with([
        'user.role',
        'journalType.compte'
    ])
    ->where('statut', 'Validé')

    // Filtre par rôle de l'utilisateur
    ->whereHas('user.role', function ($q) use ($roleType) {

        if ($roleType) {
            $q->where('type', $roleType);
        }

    })


    // Filtre journal_type_id → compte.designation
    ->whereHas('journalType.compte', function ($q) {

        $q->where(
            'designation',
            'like',
            '%banque%'
        );

    });




    // =========================
    // FILTRE DATE
    // =========================

    if (!$request->filled('date_debut') && !$request->filled('date_fin')) {

        $query->whereDate('date', today());

    }


    if ($request->filled('date_debut')) {

        $query->whereDate(
            'date',
            '>=',
            $request->date_debut
        );

    }



    if ($request->filled('date_fin')) {

        $query->whereDate(
            'date',
            '<=',
            $request->date_fin
        );

    }





    // =========================
    // PAGINATION
    // =========================

    $journaux = $query
        ->orderBy('date', 'desc')
        ->paginate(15)
        ->appends($request->all());





    // =========================
    // TOTALS
    // =========================

    $allJournaux = (clone $query)->get();



    $totaux = [

        'entrees_cdf' => $allJournaux->sum('entrees_cdf'),

        'sorties_cdf' => $allJournaux->sum('sorties_cdf'),

        'entrees_usd' => $allJournaux->sum('entrees_usd'),

        'sorties_usd' => $allJournaux->sum('sorties_usd'),

    ];





    // =========================
    // CONVERSION USD → CDF
    // =========================

    $conversion = [

        'entrees_usd_cdf' => $allJournaux->sum(
            fn($j) =>
            ($j->entrees_usd ?? 0) * $tauxValue
        ),


        'sorties_usd_cdf' => $allJournaux->sum(
            fn($j) =>
            ($j->sorties_usd ?? 0) * $tauxValue
        ),

    ];





    $totaux['total_entrees_cdf'] =
        $totaux['entrees_cdf']
        +
        $conversion['entrees_usd_cdf'];



    $totaux['total_sorties_cdf'] =
        $totaux['sorties_cdf']
        +
        $conversion['sorties_usd_cdf'];





    // =========================
    // SOLDES
    // =========================

    $soldes = [

        'cdf' =>
            $totaux['entrees_cdf']
            -
            $totaux['sorties_cdf'],


        'usd' =>
            $totaux['entrees_usd']
            -
            $totaux['sorties_usd'],


        'usd_cdf' =>
            $totaux['total_entrees_cdf']
            -
            $totaux['total_sorties_cdf'],

    ];
    return view(
        'journaux.banque',
        compact(
            'journaux',
            'totaux',
            'soldes',
            'taux'
        )
    );
}

    public function mobile(Request $request)
{
    $taux = TauxDeChange::latest()->first();
    $tauxValue = $taux->taux_de_change ?? 0;



    // =========================
    // ROLE UTILISATEUR CONNECTÉ
    // =========================
    $roleType = auth()->user()
        ->role
        ->type ?? null;



    // =========================
    // FILTRE MOBILE MONEY
    // =========================

    $query = Journaux::with([
        'user.role'
    ])
    ->where('statut', 'Validé')
    ->whereHas('user.role', function ($q) use ($roleType) {

        $q->where('type', $roleType);

    });



    // =========================
    // DATE PAR DÉFAUT
    // =========================

    if (!$request->filled('date_debut') && !$request->filled('date_fin')) {

        $query->whereDate('date', today());

    }



    // =========================
    // INTERVALLE DATE
    // =========================

    if ($request->filled('date_debut')) {

        $query->whereDate(
            'date',
            '>=',
            $request->date_debut
        );

    }


    if ($request->filled('date_fin')) {

        $query->whereDate(
            'date',
            '<=',
            $request->date_fin
        );

    }





    // =========================
    // PAGINATION
    // =========================

    $journaux = $query
        ->orderBy('date','desc')
        ->paginate(15)
        ->appends($request->all());





    // =========================
    // TOTALS
    // =========================

    $allJournaux = (clone $query)->get();



    $totaux = [

        'entrees_cdf' => $allJournaux->sum('entrees_cdf'),

        'sorties_cdf' => $allJournaux->sum('sorties_cdf'),

        'entrees_usd' => $allJournaux->sum('entrees_usd'),

        'sorties_usd' => $allJournaux->sum('sorties_usd'),

    ];





    // =========================
    // CONVERSION USD → CDF
    // =========================

    $conversion = [

        'entrees_usd_cdf' => $allJournaux->sum(function($j) use($tauxValue){

            return ($j->entrees_usd ?? 0) * $tauxValue;

        }),


        'sorties_usd_cdf' => $allJournaux->sum(function($j) use($tauxValue){

            return ($j->sorties_usd ?? 0) * $tauxValue;

        }),

    ];





    $totaux['total_entrees_cdf'] =
        $totaux['entrees_cdf']
        +
        $conversion['entrees_usd_cdf'];




    $totaux['total_sorties_cdf'] =
        $totaux['sorties_cdf']
        +
        $conversion['sorties_usd_cdf'];







    // =========================
    // SOLDES
    // =========================

    $soldes = [

        'cdf' =>
            $totaux['entrees_cdf']
            -
            $totaux['sorties_cdf'],


        'usd' =>
            $totaux['entrees_usd']
            -
            $totaux['sorties_usd'],


        'usd_cdf' =>
            $totaux['total_entrees_cdf']
            -
            $totaux['total_sorties_cdf'],

    ];





    return view(
        'journaux.mobile',
        compact(
            'journaux',
            'totaux',
            'soldes',
            'taux'
        )
    );
}
public function tresorerie(Request $request)
{

    $dateDebut = $request->date_debut
        ?? now()->startOfMonth()->format('Y-m-d');

    $dateFin = $request->date_fin
        ?? now()->format('Y-m-d');


    $tresorerie = Journaux::with([
        'journalType.compte'
    ])

    ->whereBetween('date', [
        $dateDebut,
        $dateFin
    ])

    ->where('statut','Validé')


    ->whereHas('journalType.compte', function($q){

        $q->whereIn('compte', [

            '57111',
            '57121',

            '52111',
            '52121',

            '55211',
            '55221'

        ]);

    })


    ->selectRaw('

        journal_type_id,
        monnaie,

        SUM(entrees_cdf) as entree_cdf,
        SUM(sorties_cdf) as sortie_cdf,

        SUM(entrees_usd) as entree_usd,
        SUM(sorties_usd) as sortie_usd

    ')


    ->groupBy(
        'journal_type_id',
        'monnaie'
    )


    ->get();





    /**
     * Calcul par famille
     */

    $calculSolde = function($comptes,$devise) use ($tresorerie)
    {


        return $tresorerie

        ->filter(function($ligne) use ($comptes){

            return in_array(

                $ligne
                ->journalType
                ->compte
                ->compte ?? '',

                $comptes

            );

        })


        ->sum(function($ligne) use ($devise){


            if($devise=='cdf'){

                return
                ($ligne->entree_cdf ?? 0)
                -
                ($ligne->sortie_cdf ?? 0);

            }


            return

            ($ligne->entree_usd ?? 0)
            -
            ($ligne->sortie_usd ?? 0);


        });


    };






    /**
     * ETAT DE CAISSE PAR COMPTE
     */

 /**
 * ETAT DE CAISSE PAR COMPTE
 * Filtré uniquement par statut Validé
 */

$etatCaisseData = Journaux::with([
    'journalType.compte'
])

->where('statut','Validé')


->whereHas('journalType.compte', function($q){

    $q->whereIn('compte', [

        '57111',
        '57121',

        '52111',
        '52121',

        '55211',
        '55221'

    ]);

})

->get();



$etatCaisse = $etatCaisseData

->groupBy(function($ligne){

    return $ligne
        ->journalType
        ->compte
        ->compte ?? '';

})


->map(function($lignes,$compte){


    $ligne = $lignes->first();



    $entreeCdf = $lignes->sum('entrees_cdf');
    $sortieCdf = $lignes->sum('sorties_cdf');


    $entreeUsd = $lignes->sum('entrees_usd');
    $sortieUsd = $lignes->sum('sorties_usd');



    return [

        'compte'=>$compte,


        'designation'=>

            $ligne
            ->journalType
            ->compte
            ->designation ?? '',



        'entree_cdf'=>$entreeCdf,

        'sortie_cdf'=>$sortieCdf,


        'solde_cdf'=>

            $entreeCdf - $sortieCdf,



        'entree_usd'=>$entreeUsd,

        'sortie_usd'=>$sortieUsd,


        'solde_usd'=>

            $entreeUsd - $sortieUsd,

    ];


})

->values();

    $totaux = [


        'cdf_entree' => $tresorerie
            ->sum('entree_cdf'),


        'cdf_sortie' => $tresorerie
            ->sum('sortie_cdf'),


        'cdf_solde' =>

            $tresorerie->sum('entree_cdf')
            -
            $tresorerie->sum('sortie_cdf'),




        'usd_entree' => $tresorerie
            ->sum('entree_usd'),



        'usd_sortie' => $tresorerie
            ->sum('sortie_usd'),



        'usd_solde' =>

            $tresorerie->sum('entree_usd')
            -
            $tresorerie->sum('sortie_usd'),






        // CAISSE

        'caisse_cdf'=>$calculSolde(
            ['57111','57121'],
            'cdf'
        ),


        'caisse_usd'=>$calculSolde(
            ['57111','57121'],
            'usd'
        ),





        // BANQUE

        'banque_cdf'=>$calculSolde(
            ['52111','52121'],
            'cdf'
        ),


        'banque_usd'=>$calculSolde(
            ['52111','52121'],
            'usd'
        ),





        // MOBILE

        'mobile_cdf'=>$calculSolde(
            ['55211','55221'],
            'cdf'
        ),


        'mobile_usd'=>$calculSolde(
            ['55211','55221'],
            'usd'
        ),



        // ETAT CAISSE

        'etat_caisse'=>$etatCaisse


    ];





    return view(

        'journaux.tresorerie',

        compact(

            'tresorerie',
            'totaux',
            'dateDebut',
            'dateFin'

        )

    );

}
 /**
     * Affichage formulaire
     */
    public function create()
    {
        $journalTypes = JournalType::with('compte')->get();
        $tauxDeChange = TauxDeChange::latest()->first();
        return view('journaux.create', compact(
            'journalTypes',
            'tauxDeChange'
        ));

    }




    /**
     * Enregistrement journal
     */
    public function store(Request $request)
{
    $request->validate([
        'journal_type_id' => 'required|exists:journal_types,id',
        'date' => 'required|date',
        'mode_paiement' => 'required',
        'monnaie' => 'required|in:CDF,USD',
        'taux_de_change_id' => 'required|exists:taux_de_changes,id',
        'entrees_cdf' => 'nullable|numeric',
        'entrees_usd' => 'nullable|numeric',
    ]);

    DB::beginTransaction();
    try {
        // récupération du taux par son ID
        $tauxChange = TauxDeChange::findOrFail(
            $request->taux_de_change_id
        );

        $taux = $tauxChange->taux_de_change;
        $entreeCDF = $request->entrees_cdf ?? 0;
        $entreeUSD = $request->entrees_usd ?? 0;

        // Conversion USD vers CDF

        if($request->monnaie == "USD")
        {
            $totalCDF = $entreeUSD * $taux;
        }
        else
        {
            $totalCDF = $entreeCDF;
        }

        Journaux::create([
            'user_id' => auth()->id(),
            'reference' => $this->genererNumero(),
            'journal_type_id' => $request->journal_type_id,
            'date' => $request->date,
            'mode_paiement' => $request->mode_paiement,
            'monnaie' => $request->monnaie,
            // ID du taux
            'taux_de_change_id' => $request->taux_de_change_id,
            'noms_client' => $request->noms_client,
            'telephone' => $request->telephone,
            'description' => $request->description,
            'entrees_cdf' => $entreeCDF,
            'entrees_usd' => $entreeUSD,
            'total_entrees_cdf' => $totalCDF,
            // Validation automatique
            'statut' => 'Validé',
            'date_validation' => now(),
            'valide_par' => auth()->id(),
        ]);

        DB::commit();
        return redirect()
            ->route('journaux.index')
            ->with('success','Journal enregistré avec succès');
    }
    catch(\Exception $e)
{
    DB::rollBack();

    dd($e->getMessage());
}

}
    /**
     * Génération numéro automatique
     * Exemple J-260621-001
     */
   private function genererNumero()
{
    // Année sur 2 chiffres + mois sur 2 chiffres
    $date = date('ym');


    // Nombre d'enregistrements du mois courant
    $dernier = Journaux::whereYear('created_at', date('Y'))
        ->whereMonth('created_at', date('m'))
        ->count();



    // Numéro d'ordre sur 3 chiffres
    $ordre = str_pad(
        $dernier + 1,
        3,
        '0',
        STR_PAD_LEFT
    );

    return $date . $ordre;
}
}



