<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE journaux MODIFY mode_paiement VARCHAR(50) NOT NULL DEFAULT 'espèces'");
        }

        DB::table('journaux')
            ->whereIn('mode_paiement', ['especes', 'espece', 'espèce'])
            ->update(['mode_paiement' => 'espèces']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE journaux MODIFY mode_paiement ENUM('espèces', 'banque', 'mobile_money') NOT NULL DEFAULT 'espèces'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE journaux MODIFY mode_paiement VARCHAR(50) NOT NULL DEFAULT 'especes'");
        }

        DB::table('journaux')
            ->where('mode_paiement', 'espèces')
            ->update(['mode_paiement' => 'especes']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE journaux MODIFY mode_paiement ENUM('especes', 'banque', 'mobile_money') NOT NULL DEFAULT 'especes'");
        }
    }
};
