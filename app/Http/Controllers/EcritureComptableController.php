<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteFinancialDocumentRequest;
use App\Services\FinancialDocumentService;
use Illuminate\Http\Request;
use App\Models\EcritureComptable;
use Illuminate\Support\Facades\DB;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\TauxDeChange;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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
        'user',
        'validateur'
    ]);
    $query->when($request->filled('journal_id'), fn ($q) => $q->where('journal_id', $request->integer('journal_id')));
    $query->when($request->filled('journal_ids'), function ($q) use ($request) {
        $ids = collect(explode(',', (string) $request->input('journal_ids')))->filter()->map(fn ($id) => (int) $id);
        $q->whereIn('journal_id', $ids);
    });

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
        ->orderByDesc(
            Journaux::query()
                ->select('reference')
                ->whereColumn('journaux.id', 'ecritures_comptables.journal_id')
                ->limit(1)
        )
        ->orderByRaw('CASE WHEN debit_cdf > 0 THEN 0 ELSE 1 END')
        ->orderBy('id', 'desc')
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

public function show($id, FinancialDocumentService $documents)
{
    $ecriture = EcritureComptable::with(['journal.entreeCaisse', 'journal.sortieCaisse.etatBesoin', 'journal.brcs', 'journal.clotureJournaliere', 'compte', 'user', 'validateur'])->findOrFail($id);
    $suppressionDependencies = $documents->dependencies($ecriture);
    $journal = $ecriture->journal;
    $documentLinks = collect([
        $journal?->clotureJournaliere ? ['label' => 'Voir la clôture '.$journal->clotureJournaliere->numero_cloture, 'url' => route('parametres.clotures.show', $journal->clotureJournaliere), 'icon' => 'calendar2-check'] : null,
        $journal?->brcs?->isNotEmpty() ? ['label' => $journal->brcs->count() > 1 ? 'Voir les BRC' : 'Voir le BRC', 'url' => $journal->brcs->count() === 1 ? route('brc.show', $journal->brcs->first()) : route('brc.index', ['journal_id' => $journal->id]), 'icon' => 'file-earmark-check'] : null,
        $journal?->entreeCaisse ? ['label' => "Voir le Bon d’entrée", 'url' => route('entree-caisses.show', $journal->entreeCaisse), 'icon' => 'box-arrow-in-down'] : null,
        $journal?->sortieCaisse?->etatBesoin ? ['label' => "Voir l’État de besoin", 'url' => route('etat-besoins.show', $journal->sortieCaisse->etatBesoin), 'icon' => 'file-earmark-text'] : null,
    ])->filter()->values();

    return view('Comptabilite.ecritures.show', compact('ecriture', 'suppressionDependencies', 'documentLinks'));
}

    /**
     * Validation d'une écriture
     */
public function valider(Request $request, $id)
{
    abort_unless(Auth::user()?->hasRole(['Super Admin', 'Comptable']), 403);

    $request->validate([
        'piece_justificative' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
    ]);

    $alreadyValidated = DB::transaction(function () use ($request, $id): bool {
        $ecriture = EcritureComptable::query()
            ->lockForUpdate()
            ->findOrFail($id);

        if (trim($ecriture->statut) === 'Validé') {
            return true;
        }

        $reference = mb_strtoupper(trim((string) $ecriture->piece));
        if (str_starts_with($reference, 'BSC')
            && ! $ecriture->piece_justificative
            && ! $request->hasFile('piece_justificative')) {
            throw ValidationException::withMessages([
                'piece_justificative' => 'La pièce justificative est obligatoire pour une écriture dont la référence commence par BSC.',
            ]);
        }

        $chemin = $ecriture->piece_justificative;
        if ($request->hasFile('piece_justificative')) {
            if ($chemin) {
                Storage::disk('public')->delete($chemin);
            }
            $chemin = $request->file('piece_justificative')->store('ecritures/pieces', 'public');
        }

        $ecriture->update([
            'piece_justificative' => $chemin,
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

    public function pieceJustificative($id)
    {
        Gate::authorize('viewAccountingReports');

        $ecriture = EcritureComptable::findOrFail($id);
        $chemin = $ecriture->piece_justificative;
        abort_unless(filled($chemin) && Storage::disk('public')->exists($chemin), 404, 'Pièce justificative introuvable.');

        $nom = basename($chemin);

        return response()->file(Storage::disk('public')->path($chemin), [
            'Content-Type' => Storage::disk('public')->mimeType($chemin) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$nom.'"',
        ]);
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
    public function destroy(DeleteFinancialDocumentRequest $request, $id, FinancialDocumentService $documents)
    {
        $ecriture = EcritureComptable::findOrFail($id);
        $documents->delete($ecriture, $request->validated('motif'), 'individuelle', $request);

        return back()->with(
            'success',
            'Écriture comptable placée dans la corbeille.'
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

    $taux = TauxDeChange::latest()->first();



    return view(
        'Comptabilite.ecritures.create',
        compact(
            'journaux',
            'journalTypes',
            'comptes',
            'taux'
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

        'monnaie' => 'required|in:CDF,USD',

        'sens' => 'required|in:debit,credit',

        'lignes' => 'required|array|min:1',

        'lignes.*.compte_id' => 'required|exists:liste_des_comptes,id',

        'lignes.*.libelle' => 'required|string|max:255',

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

        $libellePrincipal = collect($request->lignes)
            ->pluck('libelle')
            ->map(fn ($libelle) => trim((string) $libelle))
            ->filter()
            ->unique()
            ->implode(' / ');

        if($total <= 0){
            throw new \Exception("Le montant doit être supérieur à zéro.");
        }

        $tauxChange = 1.0;
        if ($request->monnaie === 'USD') {
            $tauxChange = (float) (TauxDeChange::latest()->value('taux_de_change') ?? 0);
            if ($tauxChange <= 0) {
                throw ValidationException::withMessages([
                    'monnaie' => 'Aucun taux de change valide n’est configuré pour convertir les USD en CDF.',
                ]);
            }
        }
        $totalCdf = round($total * $tauxChange, 2);





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


            'description'=>$libellePrincipal,



            /*
             | ENUM :
             | recette
             | depense
             | achat
             | vente
             | od
             */

            'type'=>'od',



            'monnaie'=>$request->monnaie,


            'montant_ht'=>$total,


            'montant_ttc'=>$total,


            'entrees_cdf'=> $request->monnaie === 'CDF' && $request->sens == 'debit'
                ? $total
                : 0,


            'sorties_cdf'=> $request->monnaie === 'CDF' && $request->sens == 'credit'
                ? $total
                : 0,

            'entrees_usd'=> $request->monnaie === 'USD' && $request->sens == 'debit' ? $total : 0,
            'sorties_usd'=> $request->monnaie === 'USD' && $request->sens == 'credit' ? $total : 0,



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


            'libelle'=>$libellePrincipal,




            'debit_cdf'=> $request->sens == 'debit'
                ? $totalCdf
                : 0,



            'credit_cdf'=> $request->sens == 'credit'
                ? $totalCdf
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

            $journalContrepartie = Journaux::create([
                'user_id'=>auth()->id(),
                'journal_type_id'=>$journalType->id,
                'liste_des_comptes_id'=>$ligne['compte_id'],
                'reference'=>$reference,
                'date'=>$request->date,
                'description'=>$ligne['libelle'] ?? ($request->description ?? 'Contrepartie'),
                'type'=>'od',
                'monnaie'=>$request->monnaie,
                'montant_ht'=>$ligne['montant'],
                'montant_ttc'=>$ligne['montant'],
                'entrees_cdf'=> $request->monnaie === 'CDF' && $request->sens == 'credit' ? $ligne['montant'] : 0,
                'sorties_cdf'=> $request->monnaie === 'CDF' && $request->sens == 'debit' ? $ligne['montant'] : 0,
                'entrees_usd'=> $request->monnaie === 'USD' && $request->sens == 'credit' ? $ligne['montant'] : 0,
                'sorties_usd'=> $request->monnaie === 'USD' && $request->sens == 'debit' ? $ligne['montant'] : 0,
                'statut'=>'Validé',
                'date_validation'=>now(),
                'valide_par'=>auth()->id(),
            ]);



            EcritureComptable::create([



                'user_id'=>auth()->id(),



                'journal_id'=>$journalContrepartie->id,



                'liste_des_comptes_id'=>$ligne['compte_id'],



                'date'=>$request->date,



                'libelle'=>$ligne['libelle'] ?? 'Imputation comptable',




                'debit_cdf'=> $request->sens == 'credit' ? round($ligne['montant'] * $tauxChange, 2) : 0,



                'credit_cdf'=> $request->sens == 'debit' ? round($ligne['montant'] * $tauxChange, 2) : 0,



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
