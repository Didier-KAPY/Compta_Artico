<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\Journaux;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class JournalControllerRecu extends Controller
{
    public function recu(int $id)
    {
        return view('journaux.recu', $this->donnees($id) + ['isPdf' => false]);
    }

    public function telecharger(int $id)
    {
        $donnees = $this->donnees($id) + ['isPdf' => true];

        return Pdf::loadView('journaux.recu', $donnees)
            ->setPaper('a4', 'portrait')
            ->download('recu-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $donnees['journal']->reference).'.pdf');
    }

    private function donnees(int $id): array
    {
        $selection = Journaux::findOrFail($id);
        $journaux = Journaux::with(['journalType.compte', 'user', 'validateur'])
            ->where('reference', $selection->reference)
            ->orderBy('id')
            ->get();

        $journal = $journaux->first(fn (Journaux $ligne) => mb_strtolower(trim((string) $ligne->description)) !== 'tva')
            ?? $journaux->first();
        $lignesTva = $journaux->filter(fn (Journaux $ligne) => mb_strtolower(trim((string) $ligne->description)) === 'tva');
        $lignesPrincipales = $journaux->diff($lignesTva);

        $montantHt = $lignesTva->isNotEmpty()
            ? $lignesPrincipales->sum(fn (Journaux $ligne) => (float) ($ligne->montant_ht ?: $ligne->montant_ttc))
            : (float) $journal->montant_ht;
        $montantTva = (float) $journaux->sum('montant_tva');
        $montantTtc = round($montantHt + $montantTva, 2);
        $tauxTva = (float) ($journaux->first(fn (Journaux $ligne) => (float) $ligne->taux_tva > 0)?->taux_tva ?? 0);
        $description = $lignesPrincipales->pluck('description')->filter()->unique()->implode(' / ');
        $nomPartenaire = $journaux->pluck('nom_partenaire')
            ->first(fn ($nom) => filled($nom));
        $tousValides = $journaux->every(fn (Journaux $ligne) => in_array(mb_strtolower(trim((string) $ligne->statut)), ['validé', 'valide'], true));

        $entreprise = Entreprise::first();
        $logoData = $this->imageData($entreprise?->logo);
        $cachetData = $this->imageData($entreprise?->cachet);

        return compact(
            'journal', 'journaux', 'montantHt', 'montantTva', 'montantTtc',
            'tauxTva', 'description', 'nomPartenaire', 'tousValides',
            'entreprise', 'logoData', 'cachetData'
        );
    }

    private function imageData(?string $path): ?string
    {
        $disk = Storage::disk('public');
        if (! $path || ! $disk->exists($path)) {
            return null;
        }

        $mime = $disk->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
    }
}
