<?php

namespace App\Http\Controllers;

use App\Models\Employe;
use App\Models\RhHoraire;
use App\Models\RhPresence;
use App\Services\CurrentEntreprise;
use App\Services\RhPresenceCalculationService;
use App\Services\RhWorkflowService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QrAttendanceController extends Controller
{
    public function scanner()
    {
        return view('ressources_humaines.presences.scanner');
    }

    public function scan(
        Request $request,
        CurrentEntreprise $ctx,
        RhPresenceCalculationService $calcul,
        RhWorkflowService $workflow
    ) {
        $data = $request->validate(['code' => ['required', 'string', 'max:255']]);
        $token = str_starts_with($data['code'], 'rh-attendance:')
            ? substr($data['code'], strlen('rh-attendance:'))
            : '';

        if (! preg_match('/^[A-Za-z0-9]{64}$/', $token)) {
            return response()->json(['message' => 'QR Code invalide ou désactivé.'], 422);
        }

        $entreprise = $ctx->for($request->user());
        $employe = Employe::with(['departement', 'service', 'horaire'])
            ->where('entreprise_id', $entreprise->id)
            ->where('qr_token', $token)
            ->first();

        if (! $employe) {
            return response()->json(['message' => 'QR Code invalide ou désactivé.'], 422);
        }
        if ($employe->statut !== 'Actif') {
            return response()->json(['message' => 'Le pointage est refusé : cet employé est inactif.'], 422);
        }

        $verrou = 'rh-qr-scan:'.hash('sha256', $token);
        if (! Cache::add($verrou, true, now()->addSeconds(10))) {
            return response()->json(['message' => 'Ce QR Code vient déjà d’être scanné. Veuillez patienter.'], 429);
        }

        try {
            $resultat = DB::transaction(function () use ($request, $employe, $calcul, $workflow) {
            $date = now()->toDateString();
            $heure = now()->format('H:i:s');
            $presence = RhPresence::withTrashed()->where('employe_id', $employe->id)
                ->whereDate('date', $date)
                ->lockForUpdate()
                ->first();
            $horaire = $employe->horaire ?: RhHoraire::where('entreprise_id', $employe->entreprise_id)
                ->where('par_defaut', true)->first();

            if ($presence?->trashed()) {
                $avant = $presence->toArray();
                $valeurs = $calcul->calculate($employe, ['date' => $date, 'heure_arrivee' => $heure]);
                $presence->restore();
                $presence->update([
                    'entreprise_id' => $employe->entreprise_id,
                    'user_id' => $employe->user_id,
                    'heure_arrivee' => $heure,
                    'heure_depart' => null,
                    'debut_pause' => null,
                    'fin_pause' => null,
                    'statut' => $valeurs['retard_minutes'] > 0 ? 'Retard' : 'Présent',
                    'statut_validation' => 'Brouillon',
                    'heure_debut_prevue' => $horaire?->heure_debut,
                    'heure_fin_prevue' => $horaire?->heure_fin,
                    'methode_pointage' => 'QR Code',
                    'pointe_par' => $request->user()->id,
                    'appareil_pointage' => mb_substr((string) $request->userAgent(), 0, 255),
                    'valide_par' => null,
                    'valide_le' => null,
                    'motif_correction' => null,
                    'valeurs_avant_correction' => null,
                    'heures_supplementaires_approuvees' => false,
                    'heures_supplementaires_validees' => 0,
                ] + $valeurs);
                $workflow->trace($presence, 'restauration_pointage_qr_arrivee', $request->user()->id, null, $avant, $presence->fresh()->toArray());

                return ['operation' => 'ARRIVÉE', 'presence' => $presence->fresh()];
            }

            if (! $presence) {
                $valeurs = $calcul->calculate($employe, ['date' => $date, 'heure_arrivee' => $heure]);
                $presence = RhPresence::create([
                    'entreprise_id' => $employe->entreprise_id,
                    'employe_id' => $employe->id,
                    'user_id' => $employe->user_id,
                    'date' => $date,
                    'heure_arrivee' => $heure,
                    'statut' => $valeurs['retard_minutes'] > 0 ? 'Retard' : 'Présent',
                    'statut_validation' => 'Brouillon',
                    'heure_debut_prevue' => $horaire?->heure_debut,
                    'heure_fin_prevue' => $horaire?->heure_fin,
                    'methode_pointage' => 'QR Code',
                    'pointe_par' => $request->user()->id,
                    'appareil_pointage' => mb_substr((string) $request->userAgent(), 0, 255),
                ] + $valeurs);
                $workflow->trace($presence, 'pointage_qr_arrivee', $request->user()->id, null, null, $presence->toArray());

                return ['operation' => 'ARRIVÉE', 'presence' => $presence];
            }

            if ($presence->heure_depart) {
                return ['termine' => true, 'presence' => $presence];
            }

            $avant = $presence->toArray();
            $valeurs = $calcul->calculate($employe, [
                'date' => $date,
                'heure_arrivee' => $presence->heure_arrivee,
                'heure_depart' => $heure,
                'debut_pause' => $presence->debut_pause,
                'fin_pause' => $presence->fin_pause,
            ]);
            $presence->update([
                'heure_depart' => $heure,
                'statut' => $valeurs['retard_minutes'] > 0 ? 'Retard' : 'Présent',
                'statut_validation' => 'Validé',
                'valide_par' => $request->user()->id,
                'valide_le' => now(),
                'methode_pointage' => 'QR Code',
                'pointe_par' => $request->user()->id,
                'appareil_pointage' => mb_substr((string) $request->userAgent(), 0, 255),
            ] + $valeurs);
            $workflow->trace($presence, 'pointage_qr_depart', $request->user()->id, null, $avant, $presence->fresh()->toArray());

            return ['operation' => 'DÉPART', 'presence' => $presence->fresh()];
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                return response()->json(['message' => 'Un pointage existe déjà pour cet employé aujourd’hui. Veuillez patienter puis réessayer.'], 409);
            }

            throw $exception;
        }

        if ($resultat['termine'] ?? false) {
            return response()->json(['message' => 'Pointage déjà terminé pour aujourd’hui.'], 409);
        }

        $presence = $resultat['presence'];
        $operation = $resultat['operation'];

        return response()->json([
            'message' => $operation.' ENREGISTRÉ'.($operation === 'ARRIVÉE' ? 'E' : '').' AVEC SUCCÈS',
            'operation' => $operation,
            'heure' => $operation === 'ARRIVÉE' ? substr($presence->heure_arrivee, 0, 5) : substr($presence->heure_depart, 0, 5),
            'date' => $presence->date->format('d/m/Y'),
            'employe' => [
                'nom' => trim($employe->nom.' '.$employe->postnom.' '.$employe->prenom),
                'matricule' => $employe->matricule,
                'departement' => $employe->service?->nom ?? $employe->departement?->designation ?? '—',
                'photo' => $employe->photo ? Storage::disk('public')->url($employe->photo) : null,
            ],
            'retard_minutes' => $presence->retard_minutes,
            'depart_anticipe_minutes' => $presence->depart_anticipe_minutes,
        ]);
    }
}
