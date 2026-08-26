<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteFinancialDocumentRequest;
use App\Services\FinancialDocumentService;

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
use Illuminate\Pagination\LengthAwarePaginator;


class JournalController extends Controller
{


    public function index()
    {
        return redirect()->route('journaux.create.caisse');
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

    $page = LengthAwarePaginator::resolveCurrentPage('page');
    $tresorerie = new LengthAwarePaginator(
        $tresorerie->forPage($page, 15)->values(),
        $tresorerie->count(),
        15,
        $page,
        ['path' => $request->url(), 'pageName' => 'page']
    );
    $tresorerie->appends($request->query());

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
        ->with(['journalType.compte', 'compte'])
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

    $page = LengthAwarePaginator::resolveCurrentPage('page');
    $journaux = new LengthAwarePaginator(
        $journaux->forPage($page, 20)->values(),
        $journaux->count(),
        20,
        $page,
        ['path' => $request->url(), 'pageName' => 'page']
    );
    $journaux->appends($request->query());

    return view(
        'journaux.releve',
        compact('journaux', 'comptesTresorerie', 'dateDebut', 'dateFin', 'ouverture', 'totaux')
    );
}

public function create()
{
    return redirect()->route('journaux.index')
        ->with('warning', 'Les journaux sont générés automatiquement après validation des bons.');
}

public function createCaisse(Request $request)
{
    return $this->journauxEnAttenteParNature($request, 'caisse', 'journaux.create_caisse');
}

public function createBanque(Request $request)
{
    return $this->journauxEnAttenteParNature($request, 'banque', 'journaux.create_banque');
}

public function createMobile(Request $request)
{
    return $this->journauxEnAttenteParNature($request, 'mobile_money', 'journaux.create_mobile');
}

private function journauxEnAttenteParNature(Request $request, string $nature, string $view)
{
    $request->validate([
        'reference' => 'nullable|string|max:100',
        'date_debut' => 'nullable|date',
        'date_fin' => 'nullable|date|after_or_equal:date_debut',
    ]);

    $query = Journaux::with(['journalType.compte', 'user', 'validateur'])
        ->whereHas('journalType', fn ($journalType) => $journalType->where('nature', $nature))
        ->when($request->filled('reference'), fn ($builder) => $builder->where('reference', 'like', '%'.$request->reference.'%'))
        ->when($request->filled('date_debut'), fn ($builder) => $builder->whereDate('date', '>=', $request->date_debut))
        ->when($request->filled('date_fin'), fn ($builder) => $builder->whereDate('date', '<=', $request->date_fin));

    // Sans TVA, le montant appartient uniquement au total HT.
    // Le TTC ne reprend que les bons réellement soumis à la TVA.
    $queryAvecTva = (clone $query)->where(function ($builder) {
        $builder->where('montant_tva', '>', 0)
            ->orWhere('taux_tva', '>', 0)
            ->orWhereHas('entreeCaisse', fn ($entree) => $entree->where('appliquer_tva', true))
            ->orWhereHas('sortieCaisse', fn ($sortie) => $sortie->where('appliquer_tva', true));
    });

    $totaux = [
        'nombre' => (clone $query)->where('statut', 'En attente')->count(),
        'ttc' => $queryAvecTva->sum('montant_ttc'),
        'ht' => (clone $query)->sum('montant_ht'),
        'tva' => (clone $query)->sum('montant_tva'),
    ];

    $journaux = $query->orderByDesc('date')->orderByDesc('id')->paginate(15)->withQueryString();

    return view($view, compact('journaux', 'totaux'));
}

  public function store(Request $request)
{

    $request->validate([

        'journal_type_id'=>'nullable|required_without:journal_nature|exists:journal_types,id',

        'journal_nature'=>'nullable|in:caisse,banque,mobile_money',

        'entree_caisse_id'=>'nullable|exists:entree_caisses,id',

        'liste_des_comptes_id'=>'required|exists:liste_des_comptes,id',

        'date'=>'required|date',

        'type'=>'required|in:recette,depense,achat,vente,od',

        'montant_ttc'=>'required|numeric|min:0.01',

        'appliquer_tva'=>'required|boolean',

        'taux_tva'=>'nullable|required_if:appliquer_tva,1|numeric|min:0|max:100',

        'monnaie'=>'required|in:CDF,USD',

        'mode_paiement'=>'required|in:espèces,banque,mobile_money',

        'piece_justificatif'=>'nullable|file',

        'regroupement_quotidien'=>'nullable|boolean',

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
        $bonEntree = $request->filled('entree_caisse_id')
            ? EntreeCaisse::findOrFail($request->integer('entree_caisse_id'))
            : null;
        $bonSortie = null;
        $traitementCloture = $request->boolean('regroupement_quotidien');

        if ($bonEntree && $bonEntree->statut !== 'Validé') {
            throw ValidationException::withMessages([
                'entree_caisse_id' => 'Le bon d’entrée doit être validé avant sa comptabilisation.',
            ]);
        }

        if ($bonEntree && Journaux::where('entree_caisse_id', $bonEntree->id)->exists()) {
            throw ValidationException::withMessages([
                'entree_caisse_id' => 'Ce bon d’entrée possède déjà un journal comptable.',
            ]);
        }

        if (! $traitementCloture && in_array($typeOperation, $typesEntree, true) && ! $bonEntree) {
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

        if (! $traitementCloture && in_array($typeOperation, $typesSortie, true)) {
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


            // Le compte de trésorerie vient du type; le journal garde la contrepartie.

            'liste_des_comptes_id'=>$compteOperation->id,

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
            'montant_ht'=>$montantHT,
            'taux_tva'=>$tauxTVA,
            'montant_tva'=>$montantTVA,
            'montant_ttc'=>$montantPrincipal,

            'entrees_cdf'=>$entreesCdf,

            'sorties_cdf'=>$sortiesCdf,
            'entrees_usd'=>$entreesUsd,

            'sorties_usd'=>$sortiesUsd,


            'statut'=>$traitementCloture ? 'En attente' : 'Validé',

            'statut_regroupement'=>$traitementCloture ? 'non_regroupe' : 'regroupe',


            'date_validation'=>$traitementCloture ? null : now(),


            'valide_par'=>$traitementCloture ? null : Auth::id(),


        ]);






        /*
        |--------------------------------------------------------------------------
        | ECRITURES COMPTABLES
        |--------------------------------------------------------------------------
        */


        if (! $traitementCloture && in_array($request->type, ['recette', 'vente'], true)){


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





        if (! $traitementCloture && in_array($request->type, ['depense', 'achat'], true)){


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


            "espèces" =>
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
public function showCaisse($id, FinancialDocumentService $documents)
{
    return $this->showParNature($id, $documents, 'caisse', 'journaux.show_caisse');
}

public function showBanque($id, FinancialDocumentService $documents)
{
    return $this->showParNature($id, $documents, 'banque', 'journaux.show_banque');
}

public function showMobile($id, FinancialDocumentService $documents)
{
    return $this->showParNature($id, $documents, 'mobile_money', 'journaux.show_mobile');
}

public function show($id, FinancialDocumentService $documents)
{
    return $this->showParNature($id, $documents);
}

private function showParNature($id, FinancialDocumentService $documents, ?string $natureAttendue = null, string $view = 'journaux.show')
{
    // Journal avec ses relations
    $journal = Journaux::with([
        'user',
        'journalType.compte',
        'entreeCaisse', 'sortieCaisse.etatBesoin', 'ecritures', 'brcs', 'clotureJournaliere'
    ])->findOrFail($id);
    abort_if($natureAttendue !== null && $journal->journalType?->nature !== $natureAttendue, 404);
    $suppressionDependencies = $documents->dependencies($journal);
    $documentLinks = collect([
        $journal->clotureJournaliere ? ['label' => 'Voir la clôture '.$journal->clotureJournaliere->numero_cloture, 'url' => route('parametres.clotures.show', $journal->clotureJournaliere), 'icon' => 'calendar2-check'] : null,
        $journal->sortieCaisse?->etatBesoin ? ['label' => "Voir l’État de besoin", 'url' => route('etat-besoins.show', $journal->sortieCaisse->etatBesoin), 'icon' => 'file-earmark-text'] : null,
        $journal->entreeCaisse ? ['label' => "Voir le Bon d’entrée", 'url' => route('entree-caisses.show', $journal->entreeCaisse), 'icon' => 'box-arrow-in-down'] : null,
        $journal->brcs->isNotEmpty() ? ['label' => $journal->brcs->count() > 1 ? 'Voir les BRC' : 'Voir le BRC', 'url' => $journal->brcs->count() === 1 ? route('brc.show', $journal->brcs->first()) : route('brc.index', ['journal_id' => $journal->id]), 'icon' => 'file-earmark-check'] : null,
    ])->filter()->values();


    // Dernier taux de change
    $tauxActuel = TauxDeChange::latest()->first();

    $piecePath = $journal->piece_justificatif;
    $pieceExiste = filled($piecePath) && Storage::disk('public')->exists($piecePath);
    $pieceUrl = $pieceExiste ? route('journaux.piece', $journal) : null;
    $pieceMime = $pieceExiste ? (Storage::disk('public')->mimeType($piecePath) ?: '') : '';


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

    $comptes = ListeDesComptes::orderBy('designation')->get();


    return view($view, compact(
        'journal',
        'tauxActuel',
        'lignes',
        'nature',
        'totalEntreeCDF',
        'totalSortieCDF',
        'totalEntreeUSD',
        'totalSortieUSD',
        'journalTypes',
        'comptes'
        ,'piecePath'
        ,'pieceExiste'
        ,'pieceUrl'
        ,'pieceMime'
        ,'suppressionDependencies'
        ,'documentLinks'
    ));
}

public function pieceJustificative(Request $request, Journaux $journal)
{
    Gate::authorize('manageJournaux');

    $path = $journal->piece_justificatif;
    abort_unless(filled($path) && Storage::disk('public')->exists($path), 404, 'Pièce justificative introuvable.');

    $nom = basename($path);
    if ($request->boolean('download')) {
        return Storage::disk('public')->download($path, $nom);
    }

    return response()->file(Storage::disk('public')->path($path), [
        'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
        'Content-Disposition' => 'inline; filename="'.$nom.'"',
    ]);
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

public function destroy(DeleteFinancialDocumentRequest $request, $id, FinancialDocumentService $documents)
{
    $journal = Journaux::findOrFail($id);
    $documents->delete($journal, $request->validated('motif'), $request->validated('strategie'), $request);

    return redirect()->route('journaux.index')->with('success', 'Journal placé dans la corbeille.');
}
public function rejeter(Request $request, $id)
{
    $validated = $request->validate([
        'observation' => ['required', 'string', 'min:3', 'max:2000'],
        'nom_partenaire' => ['nullable', 'string', 'max:255'],
        'telephone_partenaire' => ['nullable', 'string', 'max:50'],
        'adresse_partenaire' => ['nullable', 'string', 'max:255'],
    ], [
        'observation.required' => 'L’observation est obligatoire pour rejeter un journal.',
    ]);

    $journal = Journaux::findOrFail($id);
    Gate::authorize('rejeter', $journal);

    $journal->update([
        'statut' => 'Rejeté',
        'observation' => trim($validated['observation']),
        'nom_partenaire' => $validated['nom_partenaire'] ?? null,
        'telephone_partenaire' => $validated['telephone_partenaire'] ?? null,
        'adresse_partenaire' => $validated['adresse_partenaire'] ?? null,
        'date_validation' => Carbon::now(),
        'valide_par' => Auth::id(),
    ]);

    return redirect()
        ->route('journaux.show', $journal->id)
        ->with('success', 'Le journal a été rejeté avec succès.');
}
public function valider(Request $request, $id, WorkflowComptableService $workflow)
{
    $coordonnees = $request->validate([
        'nom_partenaire' => ['nullable', 'string', 'max:255'],
        'telephone_partenaire' => ['nullable', 'string', 'max:50'],
        'adresse_partenaire' => ['nullable', 'string', 'max:255'],
    ]);

    $journal = Journaux::with('journalType')->findOrFail($id);
    Gate::authorize('valider', $journal);

    $nature = $journal->journalType?->nature;
    $journalType = $journal->journalType;

    // Le journal des opérations diverses conserve son type d’origine.
    // Les journaux de trésorerie sont automatiquement ventilés par monnaie.
    if ($nature !== 'od') {
        $journalType = JournalType::with('compte')
            ->where('est_tresorerie', true)
            ->where('nature', $nature)
            ->where('monnaie', mb_strtoupper((string) $journal->monnaie))
            ->whereNotNull('liste_des_comptes_id')
            ->first();

        if (! $journalType?->compte) {
            throw ValidationException::withMessages([
                'journal_type' => 'Aucun journal de '.$nature.' n’est configuré pour la monnaie '.$journal->monnaie.'.',
            ]);
        }
    }

    if (! $journalType || ! $journal->liste_des_comptes_id) {
        throw ValidationException::withMessages([
            'journal_type' => 'Le journal et son compte de contrepartie doivent être configurés avant validation.',
        ]);
    }

    // La contrepartie Client/Fournisseur est volontairement différée.
    // Elle sera créée lors de l’imputation de l’écriture.
    $workflow->validerJournalAvecTva(
        $journal,
        (int) $journalType->id,
        null
    );

    Journaux::query()
        ->where('reference', $journal->reference)
        ->update([
            'nom_partenaire' => $coordonnees['nom_partenaire'] ?? null,
            'telephone_partenaire' => $coordonnees['telephone_partenaire'] ?? null,
            'adresse_partenaire' => $coordonnees['adresse_partenaire'] ?? null,
        ]);

    $ecritureId = EcritureComptable::query()
        ->where('journal_id', $journal->id)
        ->orderBy('id')
        ->value('id');

    if (! $ecritureId) {
        throw ValidationException::withMessages([
            'ecriture' => 'Aucune écriture comptable n’a été générée pour ce journal.',
        ]);
    }

    return back()->with('success', 'Journal validé : cette écriture attend son imputation.');
}
public function reouvrir($id, WorkflowComptableService $workflow)
{
    $journal = Journaux::findOrFail($id);
    Gate::authorize('reouvrir', $journal);
    $workflow->reouvrirJournal($journal);

    return back()->with('success', 'Journal réouvert avec succès.');
}

}
