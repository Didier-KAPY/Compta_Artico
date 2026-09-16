<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rh_paies', function (Blueprint $table) {
            $table->unsignedTinyInteger('active_unique')->nullable()
                ->virtualAs('CASE WHEN deleted_at IS NULL THEN 1 ELSE NULL END');
        });
        Schema::table('rh_paies', function (Blueprint $table) {
            $table->unique(['user_id', 'annee', 'mois', 'active_unique'], 'rh_paie_user_active_unique');
            $table->unique(['employe_id', 'annee', 'mois', 'active_unique'], 'rh_paie_employe_active_unique');
        });
        Schema::table('rh_paies', function (Blueprint $table) {
            $table->dropUnique('rh_paies_user_id_annee_mois_unique');
            $table->dropUnique('rh_paie_employe_periode_unique');
        });
    }

    public function down(): void
    {
        // Restoring the old constraints intentionally fails if archived duplicates exist.
        Schema::table('rh_paies', function (Blueprint $table) {
            $table->unique(['user_id', 'annee', 'mois'], 'rh_paies_user_id_annee_mois_unique');
            $table->unique(['employe_id', 'annee', 'mois'], 'rh_paie_employe_periode_unique');
        });
        Schema::table('rh_paies', function (Blueprint $table) {
            $table->dropUnique('rh_paie_user_active_unique');
            $table->dropUnique('rh_paie_employe_active_unique');
            $table->dropColumn('active_unique');
        });
    }
};
