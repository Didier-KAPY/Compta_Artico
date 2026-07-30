<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

use Carbon\Carbon;
use App\Models\Journaux;
use App\Models\EntreeCaisseLigne;
use App\Models\EntreeCaisse;
use App\Models\SortieCaisse;
use App\Models\ParametrageComptable;
use App\Models\JournalType;
use App\Models\EcritureComptable;
use App\Models\ListeDesComptes;
use App\Models\TauxDeChange;
use App\Services\WorkflowComptableService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;


class JournalController extends Controller
{


    public function index(Request $request)
{
    $journaux = Journaux::with(['journalType.compte', 'user'])

        ->when($request->filled('journal_type_id'), function ($query) use ($request) {
            $query->where('journal_type_id', $request->journal_type_id);
        })

        ->when($request->filled('reference'), function ($query) use ($request) {
            $query->where('reference', 'like', '%'.$request->reference.'%');
        })

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

        ->paginate(20)
        ->withQueryString();

    $journalTypesTresorerie = JournalType::with('compte')
        ->where('est_tresorerie', true)
        ->whereNotNull('liste_des_comptes_id')
        ->orderBy('nature')
        ->orderBy('monnaie')
        ->orderBy('code')
        ->get();


    return view(
        'journaux.index',
        compact('journaux', 'journalTypesTresorerie')
    );
}

public function banque(Request $request)
{
    return $this->afficherJournalTresorerieParNature(
        $request,
        'banque',
        'journaux.banque'
    );
}

public function mobile(Request $request)
{
    return $this->afficherJournalTresorerieParNature(
        $request,
        'mobile_money',
        'journaux.mobile'
    );
}

private function afficherJournalTresorerieParNature(
    Request $request,
    string $nature,
    string $view
    ) {
    $query = Journaux::with(['journalType.compte', 'user'])
        ->whereHas('journalType', function ($query) use ($nature) {
            $query->where('nature', $nature);
        })
        ->when($request->filled('date_debut'), function ($query) use ($request) {
            $query->whereDate('date', '>=', $request->date_debut);
        })
        ->when($request->filled('date_fin'), function ($query) use ($request) {
            $query->whereDate('date', '<=', $request->date_fin);
        });

    $taux = TauxDeChange::latest()->first();
    $valeurTaux = (float) ($taux->taux_de_change ?? 0);

    $totaux = [
        'entrees_cdf' => (clone $query)->sum('entrees_cdf'),
        'sorties_cdf' => (clone $query)->sum('sorties_cdf'),
        'entrees_usd' => (clone $query)->sum('entrees_usd'),
        'sorties_usd' => (clone $query)->sum('sorties_usd'),
    ];

    $totaux['total_entrees_cdf'] =
        $totaux['entrees_cdf'] + ($totaux['entrees_usd'] * $valeurTaux);
    $totaux['total_sorties_cdf'] =
        $totaux['sorties_cdf'] + ($totaux['sorties_usd'] * $valeurTaux);

    $soldes = [
        'cdf' => $totaux['entrees_cdf'] - $totaux['sorties_cdf'],
        'usd' => $totaux['entrees_usd'] - $totaux['sorties_usd'],
    ];
    $soldes['usd_cdf'] = $soldes['usd'] * $valeurTaux;

    $journaux = $query
        ->orderBy('date', 'desc')
        ->paginate(20)
        ->withQueryString();

    return view($view, compact('journaux', 'taux', 'totaux', 'soldes'));
}

public function tresorerie(Request $request)
{
    $request->validate(['date_debut' => 'nullable|date', 'date_fin' => 'nullable|date|after_or_equal:date_debut']);
    $dateDebut = $request->input('date_debut', now()->startOfMonth()->toDateString());
    $dateFin = $request->input('date_fin', now()->toDateString());

    $tresorerie = Journaux::query()
        ->select('journal_type_id')
        ->selectRaw('SUM(entrees_cdf) as entree_cdf')
        ->selectRaw('SUM(sorties_cdf) as sortie_cdf')
        ->selectRaw('SUM(entrees_usd) as entree_usd')
        ->selectRaw('SUM(sorties_usd) as sortie_usd')
        ->with('journalType.compte')
        ->where('statut', 'Validé')
        ->whereHas('journalType', function ($query) {
            $query->where('est_tresorerie', true);
        })
        ->whereBetween('date', [$dateDebut, $dateFin])
        ->groupBy('journal_type_id')
        ->get();

    // Le solde disponible inclut tous les mouvements jusqu'à la date de fin.
    // La requête ci-dessus reste limitée à la période pour alimenter le tableau.
    $positions = Journaux::query()
        ->select('journal_type_id')
        ->selectRaw('SUM(entrees_cdf) as entree_cdf')
        ->selectRaw('SUM(sorties_cdf) as sortie_cdf')
        ->selectRaw('SUM(entrees_usd) as entree_usd')
        ->selectRaw('SUM(sorties_usd) as sortie_usd')
        ->with('journalType')
        ->where('statut', 'Validé')
        ->whereHas('journalType', function ($query) {
            $query->where('est_tresorerie', true);
        })
        ->whereDate('date', '<=', $dateFin)
        ->groupBy('journal_type_id')
        ->get();

    $etatCaisse = $tresorerie->map(function ($ligne) {
        return [
            'compte' => $ligne->journalType?->compte?->compte ?? '',
            'designation' => $ligne->journalType?->compte?->designation ?? '',
            'solde_cdf' => $ligne->entree_cdf - $ligne->sortie_cdf,
            'solde_usd' => $ligne->entree_usd - $ligne->sortie_usd,
        ];
    });

    $totaux = [
        'cdf_entree' => $tresorerie->sum('entree_cdf'),
        'cdf_sortie' => $tresorerie->sum('sortie_cdf'),
        'usd_entree' => $tresorerie->sum('entree_usd'),
        'usd_sortie' => $tresorerie->sum('sortie_usd'),
        'etat_caisse' => $etatCaisse,
    ];

    $totaux['cdf_solde'] = $totaux['cdf_entree'] - $totaux['cdf_sortie'];
    $totaux['usd_solde'] = $totaux['usd_entree'] - $totaux['usd_sortie'];

    foreach ([
        'caisse' => 'caisse',
        'banque' => 'banque',
        'mobile' => 'mobile_money',
    ] as $cle => $nature) {
        $lignes = $positions->filter(
            fn ($ligne) => $ligne->journalType?->nature === $nature
        );

        $totaux[$cle.'_cdf'] =
            $lignes->sum('entree_cdf') - $lignes->sum('sortie_cdf');
        $totaux[$cle.'_usd'] =
            $lignes->sum('entree_usd') - $lignes->sum('sortie_usd');
    }

    return view(
        'journaux.tresorerie',
        compact('tresorerie', 'totaux', 'dateDebut', 'dateFin')
    );
}

public function releve(Request $request)
{
    $request->validate([
        'date_debut' => 'nullable|date',
        'date_fin' => 'nullable|date|after_or_equal:date_debut',
        'journal_type_id' => 'nullable|integer|exists:journal_types,id',
    ]);
    $dateDebut = $request->input('date_debut', now()->startOfMonth()->toDateString());
    $dateFin = $request->input('date_fin', now()->toDateString());

    $comptesTresorerie = JournalType::with('compte')
        ->where('est_tresorerie', true)
        ->whereNotNull('liste_des_comptes_id')
        ->orderBy('code')
        ->get();

    $baseQuery = Journaux::query()
        ->where('statut', 'Validé')
        ->whereHas('journalType', function ($query) {
            $query->where('est_tresorerie', true);
        })
        ->when($request->filled('journal_type_id'), function ($query) use ($request) {
            $query->where('journal_type_id', $request->journal_type_id);
        });

    $ouverture = (clone $baseQuery)
        ->whereDate('date', '<', $dateDebut)
        ->selectRaw('COALESCE(SUM(entrees_cdf), 0) - COALESCE(SUM(sorties_cdf), 0) AS cdf')
        ->selectRaw('COALESCE(SUM(entrees_usd), 0) - COALESCE(SUM(sorties_usd), 0) AS usd')
        ->first();

    $journaux = (clone $baseQuery)
        ->with('journalType.compte')
        ->whereBetween('date', [$dateDebut, $dateFin])
        ->orderBy('date')
        ->orderBy('id')
        ->get();

    $soldeCdf = (float) $ouverture->cdf;
    $soldeUsd = (float) $ouverture->usd;
    $journaux->each(function ($journal) use (&$soldeCdf, &$soldeUsd) {
        $soldeCdf += (float) $journal->entrees_cdf - (float) $journal->sorties_cdf;
        $soldeUsd += (float) $journal->entrees_usd - (float) $journal->sorties_usd;
        $journal->solde_progressif_cdf = $soldeCdf;
        $journal->solde_progressif_usd = $soldeUsd;
    });

    $totaux = [
        'entree_cdf' => $journaux->sum('entrees_cdf'),
        'sortie_cdf' => $journaux->sum('sorties_cdf'),
        'entree_usd' => $journaux->sum('entrees_usd'),
        'sortie_usd' => $journaux->sum('sorties_usd'),
        'solde_cdf' => $soldeCdf,
        'solde_usd' => $soldeUsd,
    ];

    return view(
        'journaux.releve',
        compact('journaux', 'comptesTresorerie', 'dateDebut', 'dateFin', 'ouverture', 'totaux')
    );
}

public function create()
{
    return $this->formulaireCreation(null, 'journaux.create');
}

public function createCaisse()
{
    return $this->formulaireCreation('caisse', 'journaux.create_caisse');
}

public function createBanque()
{
    return $this->formulaireCreation('banque', 'journaux.create_banque');
}

public function createMobile()
{
    return $this->formulaireCreation('mobile_money', 'journaux.create_mobile');
}

private function formulaireCreation(?string $natureJournal, string $view)
{

    /*
    |--------------------------------------------------------------------------
    | JOURNAUX DE TRESORERIE
    |--------------------------------------------------------------------------
    */

    $journalTypes = JournalType::with('compte')
        ->where('est_tresorerie', true)

        ->when($natureJournal, fn ($query) => $query->where('nature', $natureJournal))

        ->whereNotNull('liste_des_comptes_id')

        ->orderBy('id')

        ->get();



    /*
    |--------------------------------------------------------------------------
    | COMPTES D'OPERATION
    |--------------------------------------------------------------------------
    */

    $comptes = ListeDesComptes::orderBy('compte')

        ->get();



    /*
    |--------------------------------------------------------------------------
    | TAUX DE CHANGE
    |--------------------------------------------------------------------------
    */

    $taux = TauxDeChange::latest()->first();



    return view(
        $view,
        compact(
            'journalTypes',
            'comptes',
            'taux',
            'natureJournal'
        )
    );

}

  public function store(Request $request)
{

    $request->validate([

        'journal_type_id'=>'nullable|required_without:journal_nature|exists:journal_types,id',

        'journal_nature'=>'nullable|in:caisse,banque,mobile_money',

        'liste_des_comptes_id'=>'required|exists:liste_des_comptes,id',

        'date'=>'required|date',

        'type'=>'required|in:recette,depense,achat,vente,od',

        'montant_ttc'=>'required|numeric|min:0.01',

        'appliquer_tva'=>'required|boolean',

        'taux_tva'=>'nullable|required_if:appliquer_tva,1|numeric|min:0|max:100',

        'monnaie'=>'required|in:CDF,USD',

        'mode_paiement'=>'required|in:especes,banque,mobile_money',

        'piece_justificatif'=>'nullable|file',

    ]);

    if ($request->boolean('appliquer_tva') && $request->type === 'od') {
        throw ValidationException::withMessages([
            'type' => "La TVA nécessite une recette, une vente, une dépense ou un achat.",
        ]);
    }



    DB::transaction(function() use($request){



        /*
        |--------------------------------------------------------------------------
        | JOURNAL
        |--------------------------------------------------------------------------
        */


        $journalType = $request->filled('journal_nature')
            ? JournalType::with('compte')
                ->where('est_tresorerie', true)
                ->where('nature', $request->journal_nature)
                ->where('monnaie', $request->monnaie)
                ->whereNotNull('liste_des_comptes_id')
                ->orderBy('id')
                ->first()
            : JournalType::with('compte')->find($request->journal_type_id);

        if (! $journalType) {
            throw ValidationException::withMessages([
                'monnaie' => 'Aucun compte de journal '.$request->journal_nature.' n’est configuré en '.$request->monnaie.'.',
            ]);
        }

        if ($request->filled('journal_nature') && $journalType->nature !== $request->journal_nature) {
            throw ValidationException::withMessages([
                'journal_type_id' => 'Le journal sélectionné ne correspond pas au formulaire utilisé.',
            ]);
        }



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


        $compteOperation = ListeDesComptes::findOrFail($request->liste_des_comptes_id);




        /*
        |--------------------------------------------------------------------------
        | MONTANT
        |--------------------------------------------------------------------------
        */
        $montantPrincipal = round((float) $request->montant_ttc, 2);
        $tauxChange = 1.0;

        if ($request->monnaie === 'USD') {
            $taux = TauxDeChange::latest()->first();
            $tauxChange = (float) ($taux->taux_de_change ?? 0);

            if ($tauxChange <= 0) {
                throw ValidationException::withMessages([
                    'monnaie' => 'Aucun taux de change valide n’est configuré pour convertir les USD en CDF.',
                ]);
            }
        }

        $montantConvertiCdf = round($montantPrincipal * $tauxChange, 2);

        $entreesCdf = 0;
        $sortiesCdf = 0;
        $entreesUsd = 0;
        $sortiesUsd = 0;

        if (in_array($request->type, ['recette', 'vente'], true)) {
            if ($request->monnaie === 'CDF') {
                $entreesCdf = $montantPrincipal;
            }

            if ($request->monnaie === 'USD') {
                $entreesUsd = $montantPrincipal;
            }
        }

        if (in_array($request->type, ['depense', 'achat'], true)) {
            if ($request->monnaie === 'CDF') {
                $sortiesCdf = $montantPrincipal;
            }

            if ($request->monnaie === 'USD') {
                $sortiesUsd = $montantPrincipal;
            }
        }





        /*
        |--------------------------------------------------------------------------
        | TVA
        |--------------------------------------------------------------------------
        */


        $appliquerTVA = $request->boolean('appliquer_tva');
        $tauxTVA = $appliquerTVA ? round((float) $request->taux_tva, 2) : 0;
        $montantHT = $montantPrincipal;


        $montantTVA = 0;



        if($appliquerTVA && $tauxTVA > 0){


            $montantHT =
            $montantPrincipal /
            (1 + ($tauxTVA/100));


            $montantTVA =
            $montantPrincipal -
            $montantHT;


        }

        $montantHT = round($montantHT, 2);
        $montantTVA = $appliquerTVA
            ? round($montantPrincipal - $montantHT, 2)
            : 0;
        $montantHTCDF = round($montantHT * $tauxChange, 2);
        $montantTVACDF = $appliquerTVA
            ? round($montantConvertiCdf - $montantHTCDF, 2)
            : 0;

        $parametrageTVA = null;

        if ($appliquerTVA && $montantTVA > 0) {
            $codesTVA = in_array($request->type, ['recette', 'vente'], true)
                ? ['TVA_FACTUREE', 'TVA_DUE']
                : ['TVA_RECUPERABLE'];

            $parametrageTVA = ParametrageComptable::with('compte')
                ->whereIn('code', $codesTVA)
                ->orderByRaw(
                    'CASE code '.collect($codesTVA)->map(
                        fn ($code, $index) => "WHEN '{$code}' THEN {$index}"
                    )->implode(' ').' ELSE 99 END'
                )
                ->first();

            if (! $parametrageTVA?->compte) {
                throw ValidationException::withMessages([
                    'appliquer_tva' => 'Aucun compte TVA adapté n’est configuré dans les paramétrages comptables.',
                ]);
            }
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





        $typeOperation = mb_strtolower(trim((string) $request->type));
        $typesEntree = ['recette', 'vente'];
        $typesSortie = ['dépense', 'depense', 'achat'];
        $typeBon = match ($request->mode_paiement) {
            'banque' => 'Banque',
            'mobile_money' => 'Mobile Money',
            default => 'Caisse',
        };
        $bonEntree = null;
        $bonSortie = null;

        if (in_array($typeOperation, $typesEntree, true)) {
            $bonEntree = EntreeCaisse::create([
                'user_id' => Auth::id(),
                'numero' => 'BEC-'.now()->format('ymdHis').'-'.strtoupper(str()->random(6)),
                'date' => $request->date,
                'motif' => $request->description ?: 'Recette directe '.$reference,
                'type' => $typeBon,
                'montant' => $montantPrincipal,
                'monnaie' => $request->monnaie,
                'statut' => 'Validé',
                'observation' => 'Créé automatiquement depuis le journal '.$reference,
                'date_validation' => now(),
                'valide_par' => Auth::id(),
            ]);

            $bonEntree->lignes()->create([
                'designation' => $request->description ?: 'Recette directe '.$reference,
                'quantite' => 1,
                'prix_unitaire' => $montantPrincipal,
                'montant' => $montantPrincipal,
            ]);
        }

        if (in_array($typeOperation, $typesSortie, true)) {
            $bonSortie = SortieCaisse::create([
                'user_id' => Auth::id(),
                'numero' => 'BSC-'.now()->format('ymdHis').'-'.strtoupper(str()->random(6)),
                'date' => $request->date,
                'etat_besoin_id' => null,
                'beneficiaire' => $request->nom_partenaire ?: 'Bénéficiaire non renseigné',
                'motif' => $request->description ?: 'Dépense directe '.$reference,
                'montant' => $montantPrincipal,
                'monnaie' => $request->monnaie,
                'statut' => 'Validé',
                'type' => $typeBon,
                'observation' => 'Créé automatiquement depuis le journal '.$reference,
                'date_validation' => now(),
                'valide_par' => Auth::id(),
            ]);
        }

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

            'entree_caisse_id'=>$bonEntree?->id,

            'sortie_caisse_id'=>$bonSortie?->id,


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
            'montant_ht'=>$montantPrincipal,
            'taux_tva'=>0,
            'montant_tva'=>0,
            'montant_ttc'=>$montantPrincipal,

            'entrees_cdf'=>$entreesCdf,

            'sorties_cdf'=>$sortiesCdf,
            'entrees_usd'=>$entreesUsd,

            'sorties_usd'=>$sortiesUsd,


            'statut'=>'Validé',


            'date_validation'=>now(),


            'valide_par'=>Auth::id(),


        ]);






        /*
        |--------------------------------------------------------------------------
        | ECRITURES COMPTABLES
        |--------------------------------------------------------------------------
        */


        if(in_array($request->type, ['recette', 'vente'], true)){


            // DEBIT CAISSE/BANQUE


            EcritureComptable::create([


                'user_id'=>Auth::id(),

                'journal_id'=>$journal->id,

                'liste_des_comptes_id'=>$compteTresorerie->id,

                'date'=>$request->date,

                'piece'=>$reference,

                'libelle'=>$request->description,


                'debit_cdf'=>$montantConvertiCdf,

                'credit_cdf'=>0,


                'statut'=>'En attente',
                'valide_par'=>null,
                'date_validation'=>null,

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
                'credit_cdf'=>$montantHTCDF,
                'statut'=>'En attente',
                'valide_par'=>null,
                'date_validation'=>null,


            ]);

            if ($parametrageTVA && $montantTVA > 0) {
                EcritureComptable::create([
                    'user_id' => Auth::id(),
                    'journal_id' => $journal->id,
                    'liste_des_comptes_id' => $parametrageTVA->compte->id,
                    'date' => $request->date,
                    'piece' => $reference,
                    'libelle' => 'TVA sur '.$request->description,
                    'debit_cdf' => 0,
                    'credit_cdf' => $montantTVACDF,
                    'statut' => 'En attente',
                    'valide_par' => null,
                    'date_validation' => null,
                ]);
            }

        }





        if(in_array($request->type, ['depense', 'achat'], true)){


            // DEBIT CHARGE


            EcritureComptable::create([


                'user_id'=>Auth::id(),

                'journal_id'=>$journal->id,

                'liste_des_comptes_id'=>$compteOperation->id,


                'date'=>$request->date,

                'piece'=>$reference,

                'libelle'=>$request->description,


                'debit_cdf'=>$montantHTCDF,

                'credit_cdf'=>0,


                'statut'=>'En attente',
                'valide_par'=>null,
                'date_validation'=>null,


            ]);

            if ($parametrageTVA && $montantTVA > 0) {
                EcritureComptable::create([
                    'user_id' => Auth::id(),
                    'journal_id' => $journal->id,
                    'liste_des_comptes_id' => $parametrageTVA->compte->id,
                    'date' => $request->date,
                    'piece' => $reference,
                    'libelle' => 'TVA sur '.$request->description,
                    'debit_cdf' => $montantTVACDF,
                    'credit_cdf' => 0,
                    'statut' => 'En attente',
                    'valide_par' => null,
                    'date_validation' => null,
                ]);
            }



            // CREDIT TRESORERIE


            EcritureComptable::create([


                'user_id'=>Auth::id(),

                'journal_id'=>$journal->id,

                'liste_des_comptes_id'=>$compteTresorerie->id,


                'date'=>$request->date,

                'piece'=>$reference,

                'libelle'=>$request->description,


                'debit_cdf'=>0,

                'credit_cdf'=>$montantConvertiCdf,


                'statut'=>'En attente',
                'valide_par'=>null,
                'date_validation'=>null,

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

public function edit($id)
{
    $journal = Journaux::findOrFail($id);
    Gate::authorize('update', $journal);

    return view('journaux.edit', compact('journal'));
}

public function update(Request $request, $id)
{
    $journal = Journaux::findOrFail($id);
    Gate::authorize('update', $journal);

    $validated = $request->validate([
        'date' => 'required|date',
        'nom_partenaire' => 'nullable|string|max:255',
        'telephone_partenaire' => 'nullable|string|max:50',
        'adresse_partenaire' => 'nullable|string|max:255',
        'description' => 'required|string',
        'piece_justificatif' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
    ]);

    if ($request->hasFile('piece_justificatif')) {
        if ($journal->piece_justificatif) {
            Storage::disk('public')->delete($journal->piece_justificatif);
        }
        $validated['piece_justificatif'] = $request->file('piece_justificatif')->store('pieces', 'public');
    }

    $journal->update($validated);

    return redirect()->route('journaux.show', $journal->id)
        ->with('success', 'Journal modifié avec succès.');
}

public function destroy($id)
{
    DB::transaction(function () use ($id) {
        $journal = Journaux::lockForUpdate()->findOrFail($id);
        Gate::authorize('delete', $journal);

        if ($journal->ecritures()->exists()) {
            throw ValidationException::withMessages([
                'dependance' => 'Suppression impossible : des écritures comptables sont liées à ce Journal.',
            ]);
        }

        $piece = $journal->piece_justificatif;
        $journal->delete();

        if ($piece) {
            Storage::disk('public')->delete($piece);
        }
    });

    return redirect()->route('journaux.index')->with('success', 'Journal supprimé avec succès.');
}
public function rejeter(Request $request, $id)
{
    $journal = Journaux::findOrFail($id);

    $journal->update([
        'statut' => 'Rejeté',
        'journal_type_id' => $request->journal_type_id,
        'mode_paiement' => $request->mode_paiement,
        'date_validation' => Carbon::now(),
        'valide_par' => Auth::id(),
    ]);

    return redirect()
        ->route('journaux.show', $journal->id)
        ->with('success', 'Le journal a été rejeté avec succès.');
}
public function valider(Request $request, $id, WorkflowComptableService $workflow)
{
    $validated = $request->validate([
        'journal_type_id' => ['required', 'integer', 'exists:journal_types,id'],
    ]);

    $journal = Journaux::findOrFail($id);
    Gate::authorize('valider', $journal);
    $workflow->validerJournal($journal, (int) $validated['journal_type_id']);

    return redirect()
        ->back()
        ->with('success', 'Journal validé avec succès.');
}

public function reouvrir($id, WorkflowComptableService $workflow)
{
    $journal = Journaux::findOrFail($id);
    Gate::authorize('reouvrir', $journal);
    $workflow->reouvrirJournal($journal);

    return back()->with('success', 'Journal réouvert avec succès.');
}

}
