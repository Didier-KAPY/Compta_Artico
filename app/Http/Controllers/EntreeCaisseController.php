<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteFinancialDocumentRequest;
use App\Services\FinancialDocumentService;

use App\Models\EntreeCaisse;
use App\Models\EntreeCaisseLigne;
use App\Models\Journaux;
use App\Models\JournalType;
use App\Models\ParametrageComptable;
use App\Models\Entreprise;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Services\WorkflowComptableService;
use App\Services\DocumentNumberService;

class EntreeCaisseController extends Controller
{
    public function index()
    {
        $entrees = EntreeCaisse::with(['user', 'validateur'])
            ->latest()
            ->paginate(10);

        return view('entree_caisses.index', compact('entrees'));
    }

    public function create()
    {
        return view('entree_caisses.create', [
            'tauxTva' => (float) config('syscohada.taux_tva', 16),
        ]);
    }

    public function store(Request $request, DocumentNumberService $numbers)
{
    $this->normaliserChampsNumeriques($request);

    $request->validate([
        'date' => 'required|date',
        'monnaie' => 'required|in:CDF,USD',
        'type_bon' => 'nullable|in:BEC,BEM,BEB',
        'nom_partenaire' => 'nullable|string|max:255',
        'telephone_partenaire' => 'nullable|string|max:50',
        'adresse_partenaire' => 'nullable|string|max:255',
        'appliquer_tva' => 'nullable|boolean',
        //'type' => 'required|string|in:Caisse,Banque,Monnaie électronique',
        'designation.*' => 'required|string',
        'quantite.*' => 'required|numeric',
        'prix_unitaire.*' => 'required|numeric',
    ]);

    $typeBon = strtoupper($request->input('type_bon', 'BEC'));
    $appliquerTva = $request->boolean('appliquer_tva');
    $tauxTva = $appliquerTva ? round((float) config('syscohada.taux_tva', 16), 2) : 0;
    $motif = trim((string) collect($request->input('designation', []))
        ->first(fn ($designation) => trim((string) $designation) !== ''));

    if ($motif === '') {
        throw ValidationException::withMessages([
            'designation.0' => 'La première désignation est obligatoire.',
        ]);
    }
    $typeTresorerie = match ($typeBon) {
        'BEM' => 'Mobile Money',
        'BEB' => 'Banque',
        default => 'Caisse',
    };

    DB::beginTransaction();

    try {

        // 🔥 CREATE ENTRÉE
        $entree = EntreeCaisse::create([
            'numero' => $numbers->next($typeBon, $request->date),
            'type_bon' => $typeBon,
            'user_id' => auth()->id(),
            'date' => $request->date,
            'motif' => $motif,
            'nom_partenaire' => trim((string) $request->nom_partenaire),
            'telephone_partenaire' => filled($request->telephone_partenaire) ? trim((string) $request->telephone_partenaire) : null,
            'adresse_partenaire' => filled($request->adresse_partenaire) ? trim((string) $request->adresse_partenaire) : null,
            'monnaie' => $request->monnaie,
            'type' => $typeTresorerie,
            'appliquer_tva' => $appliquerTva,
            'taux_tva' => $tauxTva,
            'statut' => 'En attente',
            'montant' => 0
        ]);

        $total = 0;

        foreach ($request->designation as $key => $designation) {

            $qty = (float) ($request->quantite[$key] ?? 0);
            $price = (float) ($request->prix_unitaire[$key] ?? 0);

            $montant = $qty * $price;

            EntreeCaisseLigne::create([
                'entree_caisse_id' => $entree->id,
                'designation' => $designation,
                'quantite' => $qty,
                'prix_unitaire' => $price,
                'montant' => $montant,
            ]);

            $total += $montant;
        }

        // 🔥 UPDATE TOTAL
        $montantHt = $appliquerTva && $tauxTva > 0
            ? round($total / (1 + $tauxTva / 100), 2)
            : round($total, 2);
        $montantTva = $appliquerTva ? round($total - $montantHt, 2) : 0;

        $entree->update([
            'montant' => $total,
            'montant_ht' => $montantHt,
            'montant_tva' => $montantTva,
        ]);

        DB::commit();

        // ✅ IMPORTANT : retour sans redirection liste
        return back()->with('success', '✔ Bon d’entrée créé avec succès');

    } catch (\Exception $e) {

        DB::rollBack();

        // 🔥 IMPORTANT POUR DEBUG
        return back()->with('error', 'Erreur : ' . $e->getMessage());
    }
}

    public function show($id, FinancialDocumentService $documents)
    {
        $entree = EntreeCaisse::with(['user', 'lignes', 'journaux.ecritures', 'clotureJournaliere'])->findOrFail($id);
        $suppressionDependencies = $documents->dependencies($entree);
        $documentLinks = collect();
        $journalValide = $this->journauxDuBon($entree)->get()
            ->contains(fn (Journaux $journal) => $this->statutEstValide($journal->statut));

        return view('entree_caisses.show', compact('entree', 'journalValide', 'suppressionDependencies', 'documentLinks'));
    }

    public function imprimer($id)
    {
        return view('entree_caisses.document', $this->donneesDocument($id) + ['pdfMode' => false]);
    }

    public function telechargerPdf($id)
    {
        $data = $this->donneesDocument($id) + ['pdfMode' => true];
        $nom = preg_replace('/[^A-Za-z0-9_-]/', '-', $data['entree']->numero);

        return Pdf::loadView('entree_caisses.document', $data)
            ->setPaper('a4', 'portrait')
            ->download('bon-entree-'.$nom.'.pdf');
    }

    public function statistiques(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = $request->input('month');

        $query = EntreeCaisse::whereYear('date', $year)
            ->when($month, function ($query) use ($month) {
                $query->whereMonth('date', $month);
            });

        $totalEntrees = (clone $query)->count();
        $enAttente = (clone $query)->where('statut', 'En attente')->count();
        $totalValidees = (clone $query)->where('statut', 'Validé')->count();
        $totalRejetees = (clone $query)->where('statut', 'Rejeté')->count();

        $labels = [];
        $values = [];

        foreach (range(1, 12) as $numeroMois) {
            $labels[] = Carbon::create()->month($numeroMois)->format('M');
            $values[] = EntreeCaisse::whereYear('date', $year)
                ->whereMonth('date', $numeroMois)
                ->count();
        }

        $years = EntreeCaisse::query()
            ->pluck('date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->year)
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('entree_caisses.statistiques', compact(
            'year',
            'month',
            'years',
            'totalEntrees',
            'enAttente',
            'totalValidees',
            'totalRejetees',
            'labels',
            'values'
        ));
    }

    // =========================
    // VALIDATION TOGGLE
    // =========================
   public function valider(Request $request, $id)
{
    DB::beginTransaction();

    try {

        // Récupération de l'entrée de caisse
        $caisse = EntreeCaisse::with('lignes')
            ->findOrFail($id);


        // Calcul du montant
        $montant = $caisse->lignes->sum(function($ligne){

            return ($ligne->quantite ?? 0) *
                   ($ligne->prix_unitaire ?? 0);

        });


        if($montant <= 0){
            $montant = $caisse->montant ?? 0;
        }


        // Validation entrée caisse
        $caisse->update([

            'statut' => 'Validé',

            'montant' => $montant,

            'observation' => $request->observation,

            'date_validation' => now(),

            'valide_par' => auth()->id()

        ]);

        if ($caisse->origine === 'cloture') {
            DB::commit();
            return back()->with('success', 'Bon d’entrée quotidien validé sans duplication de journal.');
        }


        $natureJournal = match ($caisse->type_bon) {
            'BEM' => 'mobile_money',
            'BEB' => 'banque',
            default => 'caisse',
        };
        $routeJournal = match ($caisse->type_bon) {
            'BEM' => 'journaux.create.mobile',
            'BEB' => 'journaux.create.banque',
            default => 'journaux.create.caisse',
        };
        $modePaiement = match ($caisse->type_bon) {
            'BEM' => 'mobile_money',
            'BEB' => 'banque',
            default => 'espèces',
        };
        $journalType = JournalType::with('compte')
            ->where('est_tresorerie', true)
            ->where('nature', $natureJournal)
            ->where('monnaie', $caisse->monnaie)
            ->whereNotNull('liste_des_comptes_id')
            ->first();

        if (! $journalType?->compte) {
            throw ValidationException::withMessages([
                'journal' => 'Aucun journal '.$natureJournal.' n’est configuré en '.$caisse->monnaie.'.',
            ]);
        }

        $montantTva = $caisse->appliquer_tva ? round((float) $caisse->montant_tva, 2) : 0;
        $montantPrincipal = $montantTva > 0
            ? round((float) $caisse->montant_ht, 2)
            : $montant;

        Journaux::updateOrCreate(
            ['entree_caisse_id' => $caisse->id, 'type' => 'recette'],
            [
                'user_id' => auth()->id(),
                'journal_type_id' => $journalType->id,
                'liste_des_comptes_id' => $journalType->liste_des_comptes_id,
                'reference' => $caisse->numero,
                'date' => $caisse->date,
                'description' => $caisse->motif,
                'nom_partenaire' => $caisse->nom_partenaire,
                'telephone_partenaire' => $caisse->telephone_partenaire,
                'adresse_partenaire' => $caisse->adresse_partenaire,
                'monnaie' => $caisse->monnaie,
                'mode_paiement' => $modePaiement,
                'montant_ht' => $caisse->montant_ht ?: $montant,
                'taux_tva' => 0,
                'montant_tva' => 0,
                'montant_ttc' => $montantPrincipal,
                'entrees_cdf' => $caisse->monnaie === 'CDF' ? $montantPrincipal : 0,
                'sorties_cdf' => 0,
                'entrees_usd' => $caisse->monnaie === 'USD' ? $montantPrincipal : 0,
                'sorties_usd' => 0,
                'statut' => 'En attente',
                'date_validation' => null,
                'valide_par' => null,
            ]
        );

        if ($montantTva > 0) {
            $compteTvaId = ParametrageComptable::query()
                ->whereIn('code', ['TVA_FACTUREE', 'TVA_DUE'])
                ->orderByRaw("CASE code WHEN 'TVA_FACTUREE' THEN 0 ELSE 1 END")
                ->value('liste_des_comptes_id');

            if (! $compteTvaId) {
                throw ValidationException::withMessages([
                    'appliquer_tva' => 'Aucun compte de TVA facturée n’est configuré.',
                ]);
            }

            Journaux::updateOrCreate(
                ['entree_caisse_id' => $caisse->id, 'type' => 'depense', 'description' => 'TVA'],
                [
                    'user_id' => auth()->id(),
                    'journal_type_id' => $journalType->id,
                    'liste_des_comptes_id' => $compteTvaId,
                    'reference' => $caisse->numero,
                    'date' => $caisse->date,
                    'nom_partenaire' => $caisse->nom_partenaire,
                    'telephone_partenaire' => $caisse->telephone_partenaire,
                    'adresse_partenaire' => $caisse->adresse_partenaire,
                    'monnaie' => $caisse->monnaie,
                    'mode_paiement' => $modePaiement,
                    'montant_ht' => 0,
                    'taux_tva' => $caisse->taux_tva,
                    'montant_tva' => $montantTva,
                    'montant_ttc' => $montantTva,
                    // La TVA provient d'un bon d'entrée : elle reste donc affichée
                    // dans la colonne des entrées, dans la monnaie du bon.
                    'entrees_cdf' => $caisse->monnaie === 'CDF' ? $montantTva : 0,
                    'sorties_cdf' => 0,
                    'entrees_usd' => $caisse->monnaie === 'USD' ? $montantTva : 0,
                    'sorties_usd' => 0,
                    'statut' => 'En attente',
                    'date_validation' => null,
                    'valide_par' => null,
                ]
            );
        }

        DB::commit();

        return back()->with('success', 'Entrée validée et journal placé en attente.');
} catch(\Exception $e) {


        DB::rollBack();


        return back()->with(
            'error',
            $e->getMessage()
        );

    }
}

    public function rejeter($id)
{
    $entree = EntreeCaisse::findOrFail($id);

    if ($entree->statut === 'Rejeté') {

        $entree->update([
            'statut' => 'En attente'
        ]);

    } else {

        $entree->update([
            'statut' => 'Rejeté'
        ]);
    }

    return back()->with('success', 'Statut mis à jour.');
}

public function edit($id)
{
    $entree = EntreeCaisse::with('lignes')->findOrFail($id);

    if ($entree->statut === 'Validé') {
        return back()->with('error', 'Ce Bon validé doit d’abord être réouvert.');
    }

    return view('entree_caisses.edit', compact('entree'));
}
public function update(Request $request, $id)
{
    $this->normaliserChampsNumeriques($request);

    DB::beginTransaction();

    try {

        $entree = EntreeCaisse::findOrFail($id);

        if ($entree->statut === 'Validé') {
            return back()->with('error', 'Ce Bon validé doit d’abord être réouvert.');
        }

        // 🚨 VERIFICATION SIMPLE DU STATUT DANS JOURNAUX
            $statut = \App\Models\Journaux::where('reference', $entree->numero)
    ->value('statut');

    if ($statut === 'Validé') {

        return redirect()
            ->back()
            ->with('error', '❌ Modification impossible : ce journal est déjà validé, les fonds ont été sortis.');
    }

        // ================= CALCUL TOTAL =================
        $total = collect($request->lignes ?? [])->sum(function ($l) {
            return ($l['quantite'] ?? 0) * ($l['prix_unitaire'] ?? 0);
        });
        $tauxTva = $entree->appliquer_tva ? (float) $entree->taux_tva : 0;
        $montantHt = $tauxTva > 0 ? round($total / (1 + $tauxTva / 100), 2) : round($total, 2);
        $montantTva = $entree->appliquer_tva ? round($total - $montantHt, 2) : 0;

        // ================= UPDATE ENTREE =================
        $entree->update([
            'date' => $request->date,
            'motif' => $motif,
        //'type' => $request->type,
            'monnaie' => $request->monnaie,
            'observation' => $request->observation,
            'montant' => $total,
            'montant_ht' => $montantHt,
            'montant_tva' => $montantTva,

            // retour automatique en attente
            'statut' => 'En attente',
        ]);

        // ================= SUPPRIMER ANCIENNES LIGNES =================
        $entree->lignes()->delete();

        // ================= RECREER LIGNES =================
        if ($request->has('lignes')) {

            foreach ($request->lignes as $ligne) {

                if (!empty($ligne['designation'])) {

                    $entree->lignes()->create([
                        'designation' => $ligne['designation'],
                        'quantite' => $ligne['quantite'] ?? 0,
                        'prix_unitaire' => $ligne['prix_unitaire'] ?? 0,
                        'montant' => ($ligne['quantite'] ?? 0) * ($ligne['prix_unitaire'] ?? 0),
                    ]);
                }
            }
        }

        DB::commit();

        return redirect()
            ->route('entree-caisse.index')
            ->with('success', '✅ Entrée modifiée avec succès');

    } catch (\Exception $e) {

        DB::rollBack();

        return back()->with('error', $e->getMessage());
    }
}

public function destroy(DeleteFinancialDocumentRequest $request, $id, FinancialDocumentService $documents)
{
    $entree = EntreeCaisse::findOrFail($id);
    $documents->delete($entree, $request->validated('motif'), $request->validated('strategie'), $request);

    return redirect()
        ->route('entree-caisses.index')
        ->with('success', 'Bon d’entrée placé dans la corbeille.');
}

public function reouvrir($id, WorkflowComptableService $workflow)
{
    $entree = EntreeCaisse::findOrFail($id);
    Gate::authorize('reouvrir', $entree);
    $workflow->reouvrirEntreeCaisse($entree);

    return back()->with('success', 'Bon d’entrée remis en attente et journal provisoire supprimé avec succès.');
}

private function journauxDuBon(EntreeCaisse $entree)
{
    return Journaux::query()->where(function ($query) use ($entree) {
        $query->where('entree_caisse_id', $entree->id)
            ->orWhere('reference', $entree->numero);
    });
}

private function donneesDocument($id): array
{
    $entree = EntreeCaisse::with(['user', 'lignes'])->findOrFail($id);
    $entreprise = Entreprise::first();
    $logoData = null;

    if ($entreprise?->logo && Storage::disk('public')->exists($entreprise->logo)) {
        $mime = Storage::disk('public')->mimeType($entreprise->logo) ?: 'image/png';
        $logoData = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($entreprise->logo));
    }

    return compact('entree', 'entreprise', 'logoData');
}

private function normaliserChampsNumeriques(Request $request): void
{
    $decimal = static function ($value) {
        if (! is_string($value)) {
            return $value;
        }

        return str_replace(',', '.', preg_replace('/[\s\x{00A0}]+/u', '', trim($value)));
    };

    $request->merge([
        'taux_tva' => $decimal($request->input('taux_tva')),
        'montant_total' => $decimal($request->input('montant_total')),
        'quantite' => collect($request->input('quantite', []))->map($decimal)->all(),
        'prix_unitaire' => collect($request->input('prix_unitaire', []))->map($decimal)->all(),
        'lignes' => collect($request->input('lignes', []))->map(function ($ligne) use ($decimal) {
            $ligne['quantite'] = $decimal($ligne['quantite'] ?? null);
            $ligne['prix_unitaire'] = $decimal($ligne['prix_unitaire'] ?? null);
            return $ligne;
        })->all(),
    ]);
}

private function statutEstValide(?string $statut): bool
{
    return mb_strtolower(trim((string) $statut)) === 'validé';
}
}
