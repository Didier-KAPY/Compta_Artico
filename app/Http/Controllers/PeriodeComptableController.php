<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\ClotureJournaliere;
use App\Models\PeriodeComptable;
use App\Services\AuditLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PeriodeComptableController extends Controller
{
    public function index()
    {
        $periodes = PeriodeComptable::with(['fermeur', 'reouvreur'])->latest('date_fin')->paginate(20);
        $journeesCloturees = ClotureJournaliere::with('clotureur')
            ->where('statut', '!=', 'reouverte')
            ->latest('date_comptable')
            ->latest('revision')
            ->limit(20)
            ->get();

        return view('periodes_comptables.index', compact('periodes', 'journeesCloturees'));
    }

    public function imprimer()
    {
        return view('periodes_comptables.document', $this->donneesDocument() + ['pdfMode' => false]);
    }

    public function telechargerPdf()
    {
        return Pdf::loadView('periodes_comptables.document', $this->donneesDocument() + ['pdfMode' => true])
            ->setPaper('a4', 'landscape')
            ->download('periodes-comptables-'.now()->format('Y-m-d').'.pdf');
    }

    public function store(Request $request, AuditLogService $audit)
    {
        $data = $request->validate(['type' => ['required', 'in:mensuelle,annuelle'], 'periode' => ['required', 'date_format:Y-m']]);
        $debut = Carbon::createFromFormat('Y-m', $data['periode'])->startOfMonth();
        $fin = $data['type'] === 'annuelle' ? $debut->copy()->endOfYear() : $debut->copy()->endOfMonth();
        if ($data['type'] === 'annuelle') $debut->startOfYear();

        $periode = PeriodeComptable::updateOrCreate(
            ['type' => $data['type'], 'date_debut' => $debut->toDateString(), 'date_fin' => $fin->toDateString()],
            ['statut' => 'fermee', 'fermee_par' => Auth::id(), 'fermee_le' => now(), 'reouverte_par' => null, 'reouverte_le' => null, 'motif_reouverture' => null]
        );
        $audit->record('cloture_periode', $periode, ucfirst($data['type']).' '.$data['periode'], null, [], $periode->attributesToArray(), [], $request);
        return back()->with('success', 'Période comptable clôturée avec succès.');
    }

    public function reouvrir(Request $request, PeriodeComptable $periode, AuditLogService $audit)
    {
        $data = $request->validate(['motif' => ['required', 'string', 'min:10', 'max:1000']]);
        $avant = $periode->attributesToArray();
        $periode->update(['statut' => 'reouverte', 'reouverte_par' => Auth::id(), 'reouverte_le' => now(), 'motif_reouverture' => $data['motif']]);
        $audit->record('reouverture_periode', $periode, $periode->type, $data['motif'], $avant, $periode->fresh()->attributesToArray(), [], $request);
        return back()->with('success', 'Période comptable réouverte.');
    }

    private function donneesDocument(): array
    {
        $periodes = PeriodeComptable::with(['fermeur', 'reouvreur'])->latest('date_fin')->get();
        $entreprise = Entreprise::first();
        $logoData = null;

        if ($entreprise?->logo && Storage::disk('public')->exists($entreprise->logo)) {
            $mime = Storage::disk('public')->mimeType($entreprise->logo) ?: 'image/png';
            $logoData = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($entreprise->logo));
        }

        return compact('periodes', 'entreprise', 'logoData');
    }
}
