<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('rh_employes')
                ->where('matricule', 'like', 'LEG-%')
                ->orderBy('id')
                ->get(['id', 'entreprise_id', 'matricule'])
                ->each(function ($employe): void {
                    $suffixe = substr($employe->matricule, 4);
                    $matricule = 'EMP-'.$suffixe;
                    $increment = 1;

                    while (DB::table('rh_employes')
                        ->where('entreprise_id', $employe->entreprise_id)
                        ->where('matricule', $matricule)
                        ->where('id', '!=', $employe->id)
                        ->exists()) {
                        $matricule = 'EMP-'.$suffixe.'-'.$increment++;
                    }

                    DB::table('rh_employes')->where('id', $employe->id)->update([
                        'matricule' => $matricule,
                        'updated_at' => now(),
                    ]);
                });
        });
    }

    public function down(): void
    {
        // Aucun retour automatique : un matricule EMP peut avoir été officialisé après migration.
    }
};
