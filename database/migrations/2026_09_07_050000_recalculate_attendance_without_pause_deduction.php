<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('rh_presences')
            ->whereNull('deleted_at')
            ->whereNotNull('heure_arrivee')
            ->whereNotNull('heure_depart')
            ->orderBy('id')
            ->get()
            ->each(function ($presence): void {
                $employe = DB::table('rh_employes')->where('id', $presence->employe_id)->first();
                if (! $employe) return;

                $horaire = $employe->horaire_id
                    ? DB::table('rh_horaires')->where('id', $employe->horaire_id)->first()
                    : DB::table('rh_horaires')->where('entreprise_id', $employe->entreprise_id)->where('par_defaut', true)->first();
                if (! $horaire) return;

                $date = Carbon::parse($presence->date)->toDateString();
                $arrivee = Carbon::parse($date.' '.$presence->heure_arrivee);
                $depart = Carbon::parse($date.' '.$presence->heure_depart);
                $debutPrevu = Carbon::parse($date.' '.$horaire->heure_debut);
                $finPrevue = Carbon::parse($date.' '.$horaire->heure_fin);
                $minutes = max(0, $arrivee->diffInMinutes($depart));

                DB::table('rh_presences')->where('id', $presence->id)->update([
                    'heures_travaillees' => round($minutes / 60, 2),
                    'retard_minutes' => (int) max(0, $debutPrevu->diffInMinutes($arrivee, false) - $horaire->tolerance_retard_minutes),
                    'depart_anticipe_minutes' => (int) max(0, $depart->diffInMinutes($finPrevue, false)),
                    'heures_supplementaires' => round(max(0, $minutes / 60 - (float) $horaire->heures_normales), 2),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Le retour à un calcul retranchant une pause implicite fausserait les pointages corrigés.
    }
};
