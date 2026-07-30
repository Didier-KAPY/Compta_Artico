<?php

namespace App\Http\Controllers;

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
        return view('Comptabilite.etats_financiers.bilan', $this->donnees($request));
    }

    public function compteResultat(Request $request)
    {
        return view('Comptabilite.etats_financiers.compte_resultat', $this->donnees($request));
    }

    public function bilanPdf(Request $request)
    {
        return Pdf::loadView('Comptabilite.etats_financiers.bilan_pdf', $this->donnees($request))
            ->setPaper('a4', 'landscape')
            ->download('bilan-syscohada.pdf');
    }

    public function compteResultatPdf(Request $request)
    {
        return Pdf::loadView('Comptabilite.etats_financiers.compte_resultat_pdf', $this->donnees($request))
            ->setPaper('a4', 'landscape')
            ->download('compte-resultat-syscohada.pdf');
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
