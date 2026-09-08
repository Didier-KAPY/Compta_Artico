<?php

namespace App\Http\Controllers;

use App\Models\Employe;
use App\Services\CurrentEntreprise;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployeQrCodeController extends Controller
{
    public function show(Employe $employe, CurrentEntreprise $ctx, QrCodeService $qr)
    {
        $this->authorizeEmployee($employe, $ctx);
        abort_unless($employe->qr_token, 404, 'Aucun QR Code n’a encore été généré.');

        return view('ressources_humaines.employes.qr-code', [
            'employe' => $employe->load(['departement', 'service']),
            'qrCodeData' => $qr->dataUri($this->payload($employe)),
        ]);
    }

    public function generate(Request $request, Employe $employe, CurrentEntreprise $ctx)
    {
        $this->authorizeEmployee($employe, $ctx);
        abort_unless($employe->statut === 'Actif', 422, 'Le QR Code est réservé aux employés actifs.');

        if (! $employe->qr_token) {
            $employe->forceFill(['qr_token' => Str::random(64), 'qr_genere_le' => now()])->save();
        }

        return redirect()->route('parametres.rh.employes.qr.show', $employe)
            ->with('success', 'QR Code généré avec succès.');
    }

    public function regenerate(Request $request, Employe $employe, CurrentEntreprise $ctx)
    {
        $this->authorizeEmployee($employe, $ctx);
        abort_unless($employe->statut === 'Actif', 422, 'Le QR Code est réservé aux employés actifs.');
        $employe->forceFill(['qr_token' => Str::random(64), 'qr_genere_le' => now()])->save();

        return redirect()->route('parametres.rh.employes.qr.show', $employe)
            ->with('success', 'QR Code régénéré. L’ancien code est désormais inutilisable.');
    }

    public function download(Employe $employe, CurrentEntreprise $ctx, QrCodeService $qr)
    {
        $this->authorizeEmployee($employe, $ctx);
        abort_unless($employe->qr_token, 404);

        return response($qr->svg($this->payload($employe), 600), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-pointage-'.$employe->matricule.'.svg"',
        ]);
    }

    private function payload(Employe $employe): string
    {
        return 'rh-attendance:'.$employe->qr_token;
    }

    private function authorizeEmployee(Employe $employe, CurrentEntreprise $ctx): void
    {
        abort_unless((int) $employe->entreprise_id === (int) $ctx->for()->id, 404);
    }
}
