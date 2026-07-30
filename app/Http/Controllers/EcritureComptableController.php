<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EcritureComptable;
use Illuminate\Support\Facades\DB;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Services\WorkflowComptableService;
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
    $equilibreCDF = abs($totalDebitCDF - $totalCreditCDF);

    return view(
        'comptabilite.ecritures.liste',
        compact(
            'ecritures',
            'totalDebitCDF',
            'totalCreditCDF',
            'equilibreCDF',
        )
    );
}

    /**
     * Validation d'une écriture
     */
    public function valider($id)
{
    abort_unless(Auth::user()?->hasRole(['Super Admin', 'Comptable']), 403);

    $alreadyValidated = DB::transaction(function () use ($id): bool {
        $ecriture = EcritureComptable::query()
            ->lockForUpdate()
            ->findOrFail($id);

        if (trim($ecriture->statut) === 'Validé') {
            return true;
        }

        $ecriture->update([
            'statut' => 'Validé',
            'date_validation' => now(),
            'valide_par' => Auth::id(),
        ]);

        return false;
    });

    if ($alreadyValidated) {
        return back()->with('warning', 'Cette écriture est déjà validée.');
    }

    return back()->with(
        'success',
        'Écriture validée avec succès.'
    );
}
    public function reouvrir($id, WorkflowComptableService $workflow)
    {
        $ecriture = EcritureComptable::findOrFail($id);
        Gate::authorize('reouvrir', $ecriture);
        $workflow->reouvrirEcriture($ecriture);

        return back()->with('success', 'Écriture réouverte avec succès.');
    }

    /**
     * Formulaire de modification
     */
    public function edit($id)
    {
        $ecriture = EcritureComptable::findOrFail($id);

        if ($ecriture->statut === 'Validé') {
            return back()->with('error', 'Une écriture comptable validée ne peut plus être modifiée.');
        }

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
        ]);

        $ecriture = EcritureComptable::findOrFail($id);

        if ($ecriture->statut === 'Validé') {
            return back()->with('error', 'Une écriture comptable validée ne peut plus être modifiée.');
        }

        $ecriture->update($request->only(['date', 'libelle', 'debit_cdf', 'credit_cdf']));

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

        if ($ecriture->statut === 'Validé') {
            return back()->with('error', 'Une écriture comptable validée ne peut plus être supprimée.');
        }

        $ecriture->delete();

        return back()->with(
            'success',
            'Écriture supprimée avec succès.'
        );
    }
   public function brc(Request $request)
{
    return $this->genererBrc($request);

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
    $journalTypes = JournalType::orderBy('code')->get();

    return view('comptabilite.ecritures.form_brc', compact('journalTypes'));

}
public function genererBrc(Request $request)
{
    $donnees = $this->construireBrc($request);

    return view('comptabilite.ecritures.brc', $donnees);

}
public function brcPdf(Request $request)
{
    $donnees = $this->construireBrc($request);
    $pdf = Pdf::loadView('comptabilite.ecritures.brc_pdf', $donnees)
        ->setPaper('a4', 'landscape');

    return $pdf->download($donnees['numeroBrc'].'.pdf');

}

private function construireBrc(Request $request): array
{
    $filtres = $request->validate([
        'date_debut' => ['required', 'date'],
        'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        'journal_type_id' => ['nullable', 'integer', 'exists:journal_types,id'],
    ]);

    $dateDebut = $filtres['date_debut'];
    $dateFin = $filtres['date_fin'];
    $journalTypeId = $filtres['journal_type_id'] ?? null;

    $ecritures = EcritureComptable::with(['compte', 'journal.journalType'])
        ->where('statut', 'Validé')
        ->whereBetween('date', [$dateDebut, $dateFin])
        ->when($journalTypeId, function ($query, $id) {
            $query->whereHas('journal', fn ($journal) => $journal->where('journal_type_id', $id));
        })
        ->orderBy('date')
        ->orderBy('journal_id')
        ->orderBy('id')
        ->get();

    $brc = $ecritures->map(fn ($ecriture) => [
        'date' => $ecriture->date,
        'reference' => $ecriture->journal?->reference ?? '-',
        'journal' => $ecriture->journal?->journalType?->code ?? '-',
        'piece' => $ecriture->piece ?: ($ecriture->journal?->reference ?? '-'),
        'compte' => $ecriture->compte?->compte ?? '-',
        'designation' => $ecriture->compte?->designation ?? '-',
        'libelle' => $ecriture->libelle,
        'debit' => (float) $ecriture->debit_cdf,
        'credit' => (float) $ecriture->credit_cdf,
    ]);

    $totalDebit = round((float) $brc->sum('debit'), 2);
    $totalCredit = round((float) $brc->sum('credit'), 2);
    $ecart = round($totalDebit - $totalCredit, 2);
    $journalSelectionne = $journalTypeId ? JournalType::find($journalTypeId) : null;
    $numeroBrc = 'BRC-'.str_replace('-', '', $dateDebut).'-'.str_replace('-', '', $dateFin)
        .($journalSelectionne ? '-'.$journalSelectionne->code : '');

    return compact(
        'brc', 'totalDebit', 'totalCredit', 'ecart', 'dateDebut', 'dateFin',
        'numeroBrc', 'journalSelectionne'
    ) + ['entreprise' => \App\Models\Entreprise::first()];
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
            throw new \Exception("Ce journal n'a pas de compte associé.");
        }

        $total = 0;
        foreach($request->lignes as $ligne){
            $total += floatval($ligne['montant']);
        }

        if($total <= 0){
            throw new \Exception("Le montant doit être supérieur à zéro.");
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


            'description'=>$request->description ?? 'Opération diverse',



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


            'libelle'=>$request->description ?? 'Contrepartie',




            'debit_cdf'=> $request->sens == 'debit'
                ? $total
                : 0,



            'credit_cdf'=> $request->sens == 'credit'
                ? $total
                : 0,



            'statut'=>'Validé',
            'date_validation'=>null,
            'valide_par'=>null,


        ]);










        /*
        |--------------------------------------------------------------------------
        | Création lignes d'imputation
        |--------------------------------------------------------------------------
        */


        foreach($request->lignes as $ligne){

            if($ligne['compte_id'] == $journalType->compte->id){
                throw new \Exception("Le compte du journal ne peut pas être utilisé comme imputation.");
            }



            EcritureComptable::create([



                'user_id'=>auth()->id(),



                'journal_id'=>$journal->id,



                'liste_des_comptes_id'=>$ligne['compte_id'],



                'date'=>$request->date,



                'libelle'=>$ligne['libelle'] ?? 'Imputation comptable',




                'debit_cdf'=> $request->sens == 'credit' ? $ligne['montant'] : 0,



                'credit_cdf'=> $request->sens == 'debit' ? $ligne['montant'] : 0,



                'statut'=>'Validé',
                'date_validation'=>null,
                'valide_par'=>null,


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
