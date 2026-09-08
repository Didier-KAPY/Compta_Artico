<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('rh_presences')
            ->where('methode_pointage', 'QR Code')
            ->whereNotNull('heure_arrivee')
            ->whereNotNull('heure_depart')
            ->whereIn('statut_validation', ['Brouillon', 'À vérifier'])
            ->update([
                'statut_validation' => 'Validé',
                'valide_par' => DB::raw('COALESCE(pointe_par, valide_par)'),
                'valide_le' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Une présence exploitée en paie ne doit pas être dévalidée automatiquement.
    }
};
