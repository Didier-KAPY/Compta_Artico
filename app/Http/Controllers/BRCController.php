<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteFinancialDocumentRequest;
use App\Models\BRC;
use App\Models\EcritureComptable;
use App\Models\Entreprise;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\TauxDeChange;
use App\Services\DocumentNumberService;
use App\Services\FinancialDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BRCController extends Controller
{
    public function index(Request $request)
    {
        $afficherValidateur = $request->user()->isSuperAdmin();
        $relations = ['journalType.compte', 'lignes.compte', 'user'];
        if ($afficherValidateur) {
            $relations[] = 'validateur';
        }

        $brcs = BRC::with($relations)
            ->whereDate('date', today())
            ->when($request->filled('journal_id'), fn ($query) => $query->whereHas('journaux', fn ($journal) => $journal->whereKey($request->integer('journal_id'))))
            ->latest('date')->latest('id')->paginate(20);

        return view('BRC.index', compact('brcs', 'afficherValidateur'));
    }

    public function show(BRC $brc, FinancialDocumentService $documents)
    {
        $brc->load(['lignes.compte', 'journalType.compte', 'journaux.ecritures', 'user', 'validateur', 'clotureJournaliere']);
        if ($brc->journal_id && ! $brc->journaux->contains('id', $brc->journal_id)) {
            $legacy = Journaux::with('ecritures')->find($brc->journal_id);
            if ($legacy) {
                $brc->setRelation('journaux', $brc->journaux->push($legacy));
            }
        }
        $suppressionDependencies = $documents->dependencies($brc);
        $documentLinks = collect();

        return view('BRC.show', compact('brc', 'suppressionDependencies', 'documentLinks'));
    }

    public function pieceJustificative(BRC $brc)
    {
        abort_unless($brc->piece_justificative && Storage::disk('public')->exists($brc->piece_justificative), 404);

        return Storage::disk('public')->response($brc->piece_justificative, basename($brc->piece_justificative), [
            'Content-Disposition' => 'inline; filename="'.basename($brc->piece_justificative).'"',
        ]);
    }

    public function telechargerPdf(BRC $brc)
    {
        $data = $this->donneesDocument($brc) + ['pdfMode' => true];

        return Pdf::loadView('BRC.document', $data)
            ->setPaper('a4', 'portrait')
            ->download('brc-'.$this->nomFichier($brc).'.pdf');
    }

    public function telechargerExcel(BRC $brc)
    {
        $contenu = view('BRC.excel', $this->donneesDocument($brc))->render();

        return response("\xEF\xBB\xBF".$contenu, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="brc-'.$this->nomFichier($brc).'.xls"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    public function create()
    {
        $journaux = JournalType::with('compte')
            ->where('est_tresorerie', false)->orderBy('code')->get();
        $comptes = ListeDesComptes::orderBy('compte')->get();
        $taux = TauxDeChange::latest()->first();

        return view('BRC.create', compact('journaux', 'comptes', 'taux'));
    }

    public function store(Request $request, DocumentNumberService $numbers)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'journal_type_id' => ['required', 'exists:journal_types,id'],
            'monnaie' => ['required', 'in:CDF,USD'],
            'sens' => ['required', 'in:debit,credit'],
            'piece_justificative' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.compte_id' => ['required', 'exists:liste_des_comptes,id'],
            'lignes.*.libelle' => ['required', 'string', 'max:255'],
            'lignes.*.montant' => ['required', 'numeric', 'min:0.01'],
        ]);

        $piecePath = $request->hasFile('piece_justificative')
            ? $request->file('piece_justificative')->store('brcs/pieces', 'public')
            : null;

        $brc = DB::transaction(function () use ($data, $numbers, $piecePath) {
            $journalType = JournalType::with('compte')->findOrFail($data['journal_type_id']);
            if (! $journalType->compte) {
                throw ValidationException::withMessages(['journal_type_id' => 'Ce journal n’a pas de compte associé.']);
            }
            foreach ($data['lignes'] as $ligne) {
                if ((int) $ligne['compte_id'] === (int) $journalType->compte->id) {
                    throw ValidationException::withMessages(['lignes' => 'Le compte du journal ne peut pas être utilisé comme imputation.']);
                }
            }

            $brc = BRC::create([
                'user_id' => auth()->id(),
                'journal_type_id' => $journalType->id,
                'reference' => $numbers->next('BRC', $data['date'], $journalType->nature),
                'date' => $data['date'],
                'monnaie' => $data['monnaie'],
                'sens' => $data['sens'],
                'total' => collect($data['lignes'])->sum(fn ($ligne) => (float) $ligne['montant']),
                'piece_justificative' => $piecePath,
                'statut' => 'En attente',
            ]);
            foreach ($data['lignes'] as $ligne) {
                $brc->lignes()->create([
                    'liste_des_comptes_id' => $ligne['compte_id'],
                    'libelle' => trim($ligne['libelle']),
                    'montant' => $ligne['montant'],
                ]);
            }

            return $brc;
        });

        $this->valider($brc);

        return redirect()->route('comptabilite.imputation-compte', ['journal_id' => $brc->fresh()->journal_id])
            ->with('success', 'BRC enregistré : le BRC, le journal et toutes les écritures ont été validés.');
    }

    public function valider(BRC $brc)
    {
        abort_unless(auth()->user()?->hasRole(['Super Admin', 'Admin', 'Comptable']), 403);

        DB::transaction(function () use ($brc) {
            $brc = BRC::with(['lignes', 'journalType.compte'])->lockForUpdate()->findOrFail($brc->id);
            if ($brc->statut === 'Validé') {
                return;
            }
            if ($brc->origine === 'cloture') {
                $brc->update(['statut' => 'Validé', 'valide_par' => auth()->id(), 'date_validation' => now()]);

                return;
            }
            if (! $brc->journalType?->compte) {
                throw ValidationException::withMessages(['journal_type_id' => 'Le journal du BRC n’a pas de compte associé.']);
            }

            $taux = 1.0;
            if ($brc->monnaie === 'USD') {
                $taux = (float) (TauxDeChange::latest()->value('taux_de_change') ?? 0);
                if ($taux <= 0) {
                    throw ValidationException::withMessages(['monnaie' => 'Aucun taux de change valide n’est configuré.']);
                }
            }
            $totalCdf = round((float) $brc->total * $taux, 2);
            $libelle = $brc->lignes->pluck('libelle')->filter()->unique()->implode(' / ');

            $journal = Journaux::create([
                'user_id' => auth()->id(),
                'journal_type_id' => $brc->journal_type_id,
                'liste_des_comptes_id' => $brc->journalType->compte->id,
                'reference' => $brc->reference,
                'date' => $brc->date,
                'description' => $libelle,
                'type' => 'od',
                'monnaie' => $brc->monnaie,
                'montant_ht' => $brc->total,
                'montant_ttc' => $brc->total,
                'entrees_cdf' => $brc->monnaie === 'CDF' && $brc->sens === 'debit' ? $brc->total : 0,
                'sorties_cdf' => $brc->monnaie === 'CDF' && $brc->sens === 'credit' ? $brc->total : 0,
                'entrees_usd' => $brc->monnaie === 'USD' && $brc->sens === 'debit' ? $brc->total : 0,
                'sorties_usd' => $brc->monnaie === 'USD' && $brc->sens === 'credit' ? $brc->total : 0,
                'statut' => 'Validé',
                'date_validation' => now(),
                'valide_par' => auth()->id(),
                'piece_justificatif' => $brc->piece_justificative,
            ]);

            EcritureComptable::create([
                'user_id' => auth()->id(), 'journal_id' => $journal->id,
                'liste_des_comptes_id' => $brc->journalType->compte->id,
                'date' => $brc->date, 'piece' => $brc->reference, 'libelle' => $libelle,
                'debit_cdf' => $brc->sens === 'debit' ? $totalCdf : 0,
                'credit_cdf' => $brc->sens === 'credit' ? $totalCdf : 0,
                'statut' => 'Validé',
                'piece_justificative' => $brc->piece_justificative,
                'date_validation' => now(),
                'valide_par' => auth()->id(),
            ]);

            foreach ($brc->lignes as $ligne) {
                $montantCdf = round((float) $ligne->montant * $taux, 2);
                EcritureComptable::create([
                    'user_id' => auth()->id(), 'journal_id' => $journal->id,
                    'liste_des_comptes_id' => $ligne->liste_des_comptes_id,
                    'date' => $brc->date, 'piece' => $brc->reference, 'libelle' => $ligne->libelle,
                    'debit_cdf' => $brc->sens === 'credit' ? $montantCdf : 0,
                    'credit_cdf' => $brc->sens === 'debit' ? $montantCdf : 0,
                    'statut' => 'Validé',
                    'piece_justificative' => $brc->piece_justificative,
                    'date_validation' => now(),
                    'valide_par' => auth()->id(),
                ]);
            }

            $brc->update([
                'journal_id' => $journal->id, 'statut' => 'Validé',
                'valide_par' => auth()->id(), 'date_validation' => now(),
            ]);
            $brc->journaux()->syncWithoutDetaching([$journal->id]);
        });

        return back()->with('success', 'BRC validé : le journal et toutes les écritures comptables ont également été validés.');
    }

    public function destroy(DeleteFinancialDocumentRequest $request, BRC $brc, FinancialDocumentService $documents)
    {
        $documents->delete($brc, $request->validated('motif'), $request->validated('strategie'), $request);

        return redirect()->route('brc.index')->with('success', 'BRC placé dans la corbeille.');
    }

    private function donneesDocument(BRC $brc): array
    {
        $brc->loadMissing(['lignes.compte', 'journalType.compte', 'user', 'validateur']);
        $entreprise = Entreprise::first();
        $logoData = null;

        if ($entreprise?->logo && Storage::disk('public')->exists($entreprise->logo)) {
            $mime = Storage::disk('public')->mimeType($entreprise->logo) ?: 'image/png';
            $logoData = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($entreprise->logo));
        }

        return compact('brc', 'entreprise', 'logoData');
    }

    private function nomFichier(BRC $brc): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '-', $brc->reference ?: (string) $brc->id);
    }
}
