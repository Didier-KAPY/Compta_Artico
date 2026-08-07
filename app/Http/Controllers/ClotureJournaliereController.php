<?php

namespace App\Http\Controllers;

use App\Models\ClotureJournaliere;
use App\Models\Journaux;
use App\Services\AuditLogService;
use App\Services\ClotureJournaliereService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClotureJournaliereController extends Controller
{
    public function index(Request $request)
    {
        $clotures = ClotureJournaliere::with('clotureur')
            ->withCount(['entrees', 'sorties', 'brcs'])
            ->when($request->filled('date_debut'), fn ($q) => $q->whereDate('date_comptable', '>=', $request->date_debut))
            ->when($request->filled('date_fin'), fn ($q) => $q->whereDate('date_comptable', '<=', $request->date_fin))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->latest('date_comptable')->latest('revision')->paginate(15)->withQueryString();

        $joursOuverts = Journaux::where('statut_regroupement', 'non_regroupe')
            ->whereNull('entree_caisse_id')->whereNull('sortie_caisse_id')->doesntHave('brcs')
            ->where('statut', '!=', 'Rejeté')->selectRaw('date, COUNT(*) total')
            ->groupBy('date')->orderByDesc('date')->limit(15)->get();

        return view('clotures_journalieres.index', compact('clotures', 'joursOuverts'));
    }

    public function simulation(Request $request, ClotureJournaliereService $service)
    {
        $data = $request->validate(['date' => ['required', 'date']]);
        return view('clotures_journalieres.simulation', $service->simulation($data['date']));
    }

    public function store(Request $request, ClotureJournaliereService $service)
    {
        $data = $request->validate([
            'date' => ['required', 'date'], 'complementaire' => ['nullable', 'boolean'],
            'motif' => ['nullable', 'string', 'max:1000'],
        ]);
        $cloture = $service->cloturer($data['date'], (bool) ($data['complementaire'] ?? false), $data['motif'] ?? null);
        return redirect()->route('parametres.clotures.show', $cloture)->with('success', 'Clôture journalière générée avec succès.');
    }

    public function show(ClotureJournaliere $cloture)
    {
        $cloture->load([
            'clotureur', 'verificateur', 'entrees.journaux.ecritures',
            'sorties.journaux.ecritures', 'brcs.journaux.ecritures',
            'rattachements.journal.ecritures',
        ]);
        return view('clotures_journalieres.show', compact('cloture'));
    }

    public function verifier(ClotureJournaliere $cloture, ClotureJournaliereService $service)
    {
        $service->verifier($cloture);
        return back()->with('success', 'Clôture vérifiée : les totaux et rattachements sont cohérents.');
    }

    public function reouvrir(Request $request, ClotureJournaliere $cloture, AuditLogService $audit)
    {
        $data = $request->validate(['motif' => ['required', 'string', 'min:10', 'max:1000']]);
        DB::transaction(function () use ($cloture, $data, $request, $audit) {
            $locked = ClotureJournaliere::with(['journaux.ecritures', 'entrees', 'sorties', 'brcs'])
                ->lockForUpdate()->findOrFail($cloture->id);
            if ($locked->journaux->contains(fn ($j) => $j->statut === 'Validé' || $j->ecritures->contains('statut', 'Validé'))
                || $locked->entrees->contains('statut', 'Validé') || $locked->sorties->contains('statut', 'Validé')
                || $locked->brcs->contains('statut', 'Validé')) {
                throw ValidationException::withMessages(['statut' => 'Réouverture impossible : la chaîne contient un document ou une écriture validée.']);
            }
            $avant = $locked->attributesToArray();
            $locked->update(['statut' => 'reouverte', 'est_verifiee' => false, 'verifiee_par' => null,
                'verifiee_le' => null, 'motif_reouverture' => $data['motif']]);
            $audit->record('reouverture_cloture', $locked, $locked->numero_cloture, $data['motif'],
                $avant, $locked->fresh()->attributesToArray(), [], $request);
        });
        return back()->with('success', 'La clôture a été réouverte. Les rattachements ont été conservés pour protéger la traçabilité.');
    }
}
