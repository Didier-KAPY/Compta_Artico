<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('rh_syntheses_mensuelles')
            ->whereNotIn('statut', ['Validée', 'Verrouillée'])
            ->orderBy('id')
            ->get()
            ->each(function ($synthese): void {
                $debut = Carbon::create($synthese->annee, $synthese->mois, 1)->startOfMonth();
                $fin = $debut->copy()->endOfMonth();
                $pointages = DB::table('rh_presences')
                    ->whereNull('deleted_at')
                    ->where('employe_id', $synthese->employe_id)
                    ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
                    ->where('statut_validation', 'Validé')
                    ->get();

                DB::table('rh_syntheses_mensuelles')->where('id', $synthese->id)->update([
                    'jours_presents' => $pointages->whereIn('statut', ['Présent', 'Retard', 'Télétravail'])->count(),
                    'jours_conges_payes' => $pointages->whereIn('statut', ['Congé', 'Absence justifiée', 'Maladie', 'Permission'])->count(),
                    'jours_mission' => $pointages->where('statut', 'Mission')->count(),
                    'jours_absence_non_remuneree' => $pointages->whereIn('statut', ['Absent', 'Absence non justifiée'])->count(),
                    'nombre_retards' => $pointages->where('retard_minutes', '>', 0)->count(),
                    'retard_total_minutes' => $pointages->sum('retard_minutes'),
                    'heures_supplementaires_validees' => $pointages->where('heures_supplementaires_approuvees', true)->sum('heures_supplementaires_validees'),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Les synthèses déjà utilisées ne doivent pas être recalculées à rebours.
    }
};
