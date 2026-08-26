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
use App\Models\BRC;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Services\WorkflowComptableService;
use App\Services\DocumentNumberService;
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
    if (! $request->filled('journal_id') && ! $request->filled('journal_ids')
        && ! $request->filled('date_debut') && ! $request->filled('date_fin')) {

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
        ->orderByDesc('created_at')
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

public function imputationCompte(Request $request)
{
    abort_unless(Auth::user()?->hasRole(['Super Admin', 'Admin', 'Comptable']), 403);

    $journaux = Journaux::with([
            'journalType.compte', 'brcs.validateur', 'brcs.lignes',
            'ecritures' => fn ($query) => $query->with('compte'),
            'user', 'validateur',
        ])
        ->where('statut', 'Validé')
        ->whereHas('brcs')
        ->when($request->filled('reference'), function ($query) use ($request) {
            $reference = trim((string) $request->input('reference'));
            $query->where('reference', 'like', '%'.$reference.'%');
        })
        ->when($request->filled('date_debut'), fn ($query) => $query->whereDate('date', '>=', $request->date_debut))
        ->when($request->filled('date_fin'), fn ($query) => $query->whereDate('date', '<=', $request->date_fin))
        ->when(! $request->filled('date_debut') && ! $request->filled('date_fin'), fn ($query) => $query->whereDate('date', today()))
        ->when($request->filled('journal_id'), fn ($query) => $query->whereKey($request->integer('journal_id')))
        ->orderByDesc('date')
        ->orderByDesc('id')
        ->paginate(15)
        ->withQueryString();

    $comptes = ListeDesComptes::orderBy('compte')->get();

    return view('Comptabilite.imputation', compact('journaux', 'comptes'));
}

public function traiterJournal(Request $request, Journaux $journal, WorkflowComptableService $workflow)
{
    abort_unless(Auth::user()?->hasRole(['Super Admin', 'Comptable']), 403);
    abort_unless($journal->brcs()->exists(), 404);

    $validated = $request->validate([
        'comptes' => ['required', 'array'],
        'comptes.*' => ['required', 'integer', 'exists:liste_des_comptes,id'],
        'piece_justificative' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
    ]);

    $reference = mb_strtoupper(trim((string) $journal->reference));
    $pieceObligatoire = preg_match('/^(BSC|BSB|BSM)/', $reference) === 1;
    $pieceExistante = filled($journal->piece_justificatif)
        || $journal->ecritures()->whereNotNull('piece_justificative')->exists();

    if ($pieceObligatoire && ! $pieceExistante && ! $request->hasFile('piece_justificative')) {
        throw ValidationException::withMessages([
            'piece_justificative' => 'La pièce justificative est obligatoire pour les bons BSC, BSB et BSM.',
        ]);
    }

    $chemin = $request->hasFile('piece_justificative')
        ? $request->file('piece_justificative')->store('ecritures/pieces', 'public')
        : null;

    DB::transaction(function () use ($journal, $validated, $chemin, $workflow) {
        $ecritures = EcritureComptable::where('journal_id', $journal->id)
            ->where('statut', 'En attente')
            ->lockForUpdate()
            ->get();

        if ($ecritures->isEmpty()) {
            throw ValidationException::withMessages(['statut' => 'Ce journal ne contient aucune écriture à imputer.']);
        }

        foreach ($ecritures as $ecriture) {
            $compteId = $validated['comptes'][$ecriture->id] ?? null;
            if (! $compteId) {
                throw ValidationException::withMessages(['comptes' => 'Tous les comptes doivent être renseignés.']);
            }

            $ecriture->update([
                'liste_des_comptes_id' => $compteId,
                'piece_justificative' => $chemin ?: $ecriture->piece_justificative,
            ]);
            $workflow->validerEcriture($ecriture);
        }
    });

    return redirect()->route('ecritures.liste', ['journal_id' => $journal->id])
        ->with('success', 'Journal imputé et écritures comptables validées avec succès.');
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

    $reference = mb_strtoupper(trim((string) $ecriture->piece));
    $ecrituresReference = EcritureComptable::with('compte')
        ->when($reference !== '',
            fn ($query) => $query->whereRaw('UPPER(TRIM(piece)) = ?', [$reference]),
            fn ($query) => $query->whereKey($ecriture->id)
        )
        ->orderByRaw('CASE WHEN debit_cdf > 0 THEN 0 ELSE 1 END')
        ->orderBy('id')
        ->get();
    $comptes = ListeDesComptes::orderBy('compte')->get();
    $pieceObligatoire = preg_match('/^(BSC|BSB|BSM)/', $reference) === 1;
    $estImpute = $ecrituresReference->isNotEmpty()
        && $ecrituresReference->every(fn ($ligne) => $ligne->statut === 'Validé');
    $ligneAuDebit = (float) $ecriture->debit_cdf > 0;
    $lignesOpposees = $ecrituresReference->filter(fn ($ligne) =>
        (int) $ligne->journal_id === (int) $ecriture->journal_id
        && ($ligneAuDebit ? (float) $ligne->credit_cdf > 0 : (float) $ligne->debit_cdf > 0)
    )->values();

    $bon = $journal?->entreeCaisse ?? $journal?->sortieCaisse;
    $montantTva = (float) ($bon?->montant_tva ?? 0);
    if ($montantTva > 0 && mb_strtoupper((string) $bon?->monnaie) === 'USD') {
        $montantTva *= (float) (TauxDeChange::latest()->value('taux_de_change') ?? 0);
    }
    $montantTresorerie = max(
        (float) $ecriture->debit_cdf,
        (float) $ecriture->credit_cdf
    );
    $montantTotalAImputer = $montantTresorerie;
    $montantContrepartie = max(0, $montantTresorerie - $montantTva);

    return view('Comptabilite.ecritures.show', compact(
        'ecriture', 'ecrituresReference', 'lignesOpposees', 'comptes',
        'pieceObligatoire', 'estImpute', 'montantTva', 'montantContrepartie',
        'montantTotalAImputer',
        'suppressionDependencies', 'documentLinks'
    ));
}

    /**
     * Validation d'une écriture
     */
public function valider(Request $request, $id)
{
    abort_unless(Auth::user()?->hasRole(['Super Admin', 'Comptable']), 403);

    $request->validate([
        'imputations' => ['nullable', 'array', 'min:1'],
        'imputations.*.liste_des_comptes_id' => ['required_with:imputations', 'integer', 'exists:liste_des_comptes,id'],
        'imputations.*.montant' => ['required_with:imputations', 'numeric', 'gt:0'],
        'imputations.*.piece_justificative' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        // Compatibilité avec les anciens formulaires et appels internes.
        'liste_des_comptes_id' => ['nullable', 'required_without:imputations', 'integer', 'exists:liste_des_comptes,id'],
        'piece_justificative' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
    ]);

    $imputations = collect($request->input('imputations', []));
    if ($imputations->isEmpty()) {
        $imputations = collect([['liste_des_comptes_id' => $request->integer('liste_des_comptes_id')]]);
    }

    $alreadyValidated = DB::transaction(function () use ($request, $id, $imputations): bool {
        $ecriture = EcritureComptable::query()->lockForUpdate()->findOrFail($id);
        $reference = mb_strtoupper(trim((string) $ecriture->piece));
        $ecritures = EcritureComptable::query()
            ->when($reference !== '',
                fn ($query) => $query->whereRaw('UPPER(TRIM(piece)) = ?', [$reference]),
                fn ($query) => $query->whereKey($ecriture->id)
            )
            ->lockForUpdate()->get();

        $enAttente = $ecritures->filter(fn ($ligne) => trim((string) $ligne->statut) !== 'Validé');
        if ($enAttente->isEmpty()) return true;

        $ligneAuDebit = (float) $ecriture->debit_cdf > 0;
        $montantContrepartie = max(
            (float) $ecriture->debit_cdf,
            (float) $ecriture->credit_cdf
        );
        $lignesOpposees = $ecritures->filter(fn ($ligne) =>
            (int) $ligne->journal_id === (int) $ecriture->journal_id
            && ($ligneAuDebit ? (float) $ligne->credit_cdf > 0 : (float) $ligne->debit_cdf > 0)
        )->values();

        if ($lignesOpposees->isEmpty()) {
            $lignesOpposees = collect();
            foreach ($imputations as $imputation) {
                $montantLigne = (float) ($imputation['montant'] ?? $montantContrepartie);
                $ligneOpposee = EcritureComptable::create([
                    'user_id' => Auth::id() ?? $ecriture->user_id,
                    'journal_id' => $ecriture->journal_id,
                    'liste_des_comptes_id' => (int) $imputation['liste_des_comptes_id'],
                    'date' => $ecriture->date,
                    'piece' => $ecriture->piece,
                    'libelle' => $ecriture->libelle,
                    'debit_cdf' => $ligneAuDebit ? 0 : $montantLigne,
                    'credit_cdf' => $ligneAuDebit ? $montantLigne : 0,
                    'statut' => 'En attente',
                ]);
                $lignesOpposees->push($ligneOpposee);
                $ecritures->push($ligneOpposee);
                $enAttente->push($ligneOpposee);
            }

        } elseif ($imputations->count() > $lignesOpposees->count()) {
            throw ValidationException::withMessages([
                'imputations' => 'Le nombre de lignes ajoutées dépasse le nombre de contreparties disponibles.',
            ]);
        }

        $pieceObligatoire = preg_match('/^(BSC|BSB|BSM)/', $reference) === 1;
        $fichierCommun = $request->file('piece_justificative')
            ?? $request->file('imputations.0.piece_justificative');
        $pieceExistanteGroupe = $ecritures
            ->first(fn ($ligne) => filled($ligne->piece_justificative))
            ?->piece_justificative;
        if ($pieceObligatoire && ! $pieceExistanteGroupe && ! $fichierCommun) {
            throw ValidationException::withMessages([
                'piece_justificative' => 'Une pièce justificative est obligatoire pour cette imputation BSC, BSB ou BSM.',
            ]);
        }

        foreach ($imputations as $index => $imputation) {
            $ligneOpposee = $lignesOpposees->get($index);
            if (! $ligneOpposee) {
                throw ValidationException::withMessages(['imputations' => 'La ligne de sens opposé est introuvable.']);
            }


            $montantImpute = (float) ($imputation['montant'] ?? max(
                (float) $ligneOpposee->debit_cdf,
                (float) $ligneOpposee->credit_cdf
            ));
            $ligneOpposee->update([
                'liste_des_comptes_id' => (int) $imputation['liste_des_comptes_id'],
                'debit_cdf' => $ligneAuDebit ? 0 : $montantImpute,
                'credit_cdf' => $ligneAuDebit ? $montantImpute : 0,
            ]);
        }

        $equilibre = EcritureComptable::query()
            ->when($reference !== '',
                fn ($query) => $query->whereRaw('UPPER(TRIM(piece)) = ?', [$reference]),
                fn ($query) => $query->whereKey($ecriture->id)
            )
            ->where('statut', '!=', 'Validé')
            ->selectRaw('COALESCE(SUM(debit_cdf), 0) AS total_debit, COALESCE(SUM(credit_cdf), 0) AS total_credit')
            ->first();
        $ecart = abs((float) $equilibre->total_debit - (float) $equilibre->total_credit);
        if ($ecart > 0.005) {
            throw ValidationException::withMessages([
                'imputations' => 'Écriture non équilibrée : écart de '.number_format($ecart, 2, ',', ' ').' CDF.',
            ]);
        }

        $pieceCommune = $fichierCommun
            ? $fichierCommun->store('ecritures/pieces', 'public')
            : $pieceExistanteGroupe;
        foreach ($enAttente as $ligne) {
            $ligne->update([
                'piece_justificative' => $ligne->piece_justificative ?: $pieceCommune,
                'statut' => 'Validé',
                'date_validation' => now(),
                'valide_par' => Auth::id(),
            ]);
        }
        return false;
    });

    if ($alreadyValidated) return back()->with('warning', 'Cette écriture est déjà validée.');

    return back()->with('success', 'Imputation enregistrée : les contreparties et la TVA sont maintenant visibles.');
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
   public function store(Request $request, DocumentNumberService $numbers)
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



    DB::transaction(function () use ($request, $numbers) {


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


        $reference = $numbers->next('BRC', $request->date, $journalType->nature);






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

        $journalIds = [$journal->id];








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

            $journalIds[] = $journalContrepartie->id;



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

        $brc = BRC::create([
            'user_id' => auth()->id(),
            'journal_type_id' => $journalType->id,
            'journal_id' => $journal->id,
            'reference' => $reference,
            'date' => $request->date,
            'monnaie' => $request->monnaie,
            'sens' => $request->sens,
            'total' => $total,
            'statut' => 'Validé',
            'valide_par' => auth()->id(),
            'date_validation' => now(),
            'origine' => 'imputation',
            'genere_automatiquement_le' => now(),
        ]);

        foreach ($request->lignes as $ligne) {
            $brc->lignes()->create([
                'liste_des_comptes_id' => $ligne['compte_id'],
                'libelle' => trim($ligne['libelle']),
                'montant' => $ligne['montant'],
            ]);
        }

        $brc->journaux()->sync($journalIds);



    });





    return redirect()

        ->route('ecritures.create')

        ->with(

            'success',

            'Écriture comptable et BRC enregistrés avec succès.'

        );

}
}
