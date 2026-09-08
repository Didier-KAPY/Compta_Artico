<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE journaux MODIFY mode_paiement ENUM('espèces', 'banque', 'mobile_money') NOT NULL DEFAULT 'espèces'");
        }
    }

    public function down(): void
    {
    }
};
