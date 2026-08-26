<?php

namespace App\Http\Controllers;

use App\Models\BilanInitial;
use App\Models\Entreprise;
use App\Services\SyscohadaEtatFinancierService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class EtatFinancierController extends Controller
{
    public function __construct(private readonly SyscohadaEtatFinancierService $service)
    {
    }

    public function index(Request $request)
    {
        return view('Comptabilite.etats_financiers.index', $this->filtres($request, false));
    }

    public function bilan(Request $request)
    {
        $donnees = $this->donnees($request);
        $donnees['bilanInitial'] = BilanInitial::query()
            ->whereDate('date_debut', $donnees['dateDebut'])
            ->whereDate('date_fin', $donnees['dateFin'])
            ->latest('id')
            ->first();

        return view('Comptabilite.etats_financiers.bilan', $donnees);
    }

    public function archiverBilanInitial(Request $request)
    {
        $validees = $request->validate([
            'libelle' => ['required', 'string', 'max:255'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);
        $archiveExistante = BilanInitial::query()
            ->whereDate('date_debut', $validees['date_debut'])
            ->whereDate('date_fin', $validees['date_fin'])
            ->first();

        if ($archiveExistante) {
            return redirect()->route('comptabilite.etats-financiers.bilan', [
                'date_debut' => $validees['date_debut'],
                'date_fin' => $validees['date_fin'],
            ]);
        }

        $donnees = $this->donnees($request);
        $bilan = $donnees['etats']['bilan'];

        BilanInitial::create([
            'user_id' => $request->user()->id,
            'libelle' => $validees['libelle'],
            'date_debut' => $validees['date_debut'],
            'date_fin' => $validees['date_fin'],
            'total_actif' => $bilan['total_actif'],
            'total_passif' => $bilan['total_passif'],
            'ecart' => $bilan['ecart'],
            'donnees' => $donnees['etats'],
        ]);

        return redirect()
            ->route('comptabilite.etats-financiers.bilan', [
                'date_debut' => $validees['date_debut'],
                'date_fin' => $validees['date_fin'],
            ])
            ->with('success', 'Le bilan initial a été archivé avec succès.');
    }

    public function consulterBilanInitial(BilanInitial $bilanInitial)
    {
        return view('Comptabilite.etats_financiers.bilan_initial', [
            'dateDebut' => $bilanInitial->date_debut->toDateString(),
            'dateFin' => $bilanInitial->date_fin->toDateString(),
            'monnaie' => 'CDF',
            'etats' => $bilanInitial->donnees,
            'entreprise' => Entreprise::first(),
            'bilanInitial' => $bilanInitial,
        ]);
    }

    public function supprimerBilanInitial(BilanInitial $bilanInitial)
    {
        $periode = [
            'date_debut' => $bilanInitial->date_debut->toDateString(),
            'date_fin' => $bilanInitial->date_fin->toDateString(),
        ];

        $bilanInitial->delete();

        return redirect()
            ->route('comptabilite.etats-financiers.bilan', $periode)
            ->with('success', 'Le bilan initial archivé a été supprimé.');
    }
    public function compteResultat(Request $request)
    {
        return view('Comptabilite.etats_financiers.compte_resultat', $this->donnees($request));
    }

    public function bilanPdf(Request $request)
    {
        return Pdf::loadView('Comptabilite.etats_financiers.bilan_pdf', $this->donnees($request))
            ->setPaper('a4', 'landscape')
            ->download('bilan-final.pdf');
    }

    public function compteResultatPdf(Request $request)
    {
        return Pdf::loadView('Comptabilite.etats_financiers.compte_resultat_pdf', $this->donnees($request))
            ->setPaper('a4', 'landscape')
            ->download('compte-resultat.pdf');
    }

    private function donnees(Request $request): array
    {
        $filtres = $this->filtres($request);
        $etats = $this->service->generer(
            CarbonImmutable::parse($filtres['dateDebut']),
            CarbonImmutable::parse($filtres['dateFin'])
        );

        return $filtres + [
            'etats' => $etats,
            'entreprise' => Entreprise::first(),
        ];
    }

    private function filtres(Request $request, bool $valider = true): array
    {
        $valeurs = [
            'date_debut' => $request->input('date_debut', now()->startOfYear()->toDateString()),
            'date_fin' => $request->input('date_fin', now()->toDateString()),
        ];

        if ($valider) {
            validator($valeurs, [
                'date_debut' => ['required', 'date'],
                'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            ])->validate();
        }

        return [
            'dateDebut' => $valeurs['date_debut'],
            'dateFin' => $valeurs['date_fin'],
            'monnaie' => 'CDF',
        ];
    }
}
