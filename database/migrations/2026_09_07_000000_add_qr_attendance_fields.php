<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rh_employes', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('matricule');
            $table->timestamp('qr_genere_le')->nullable()->after('qr_token');
        });

        Schema::table('rh_presences', function (Blueprint $table) {
            $table->string('methode_pointage', 30)->default('Manuel')->after('heure_depart');
            $table->foreignId('pointe_par')->nullable()->after('methode_pointage')->constrained('users')->nullOnDelete();
            $table->string('appareil_pointage', 255)->nullable()->after('pointe_par');
        });
    }

    public function down(): void
    {
        Schema::table('rh_presences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pointe_par');
            $table->dropColumn(['methode_pointage', 'appareil_pointage']);
        });
        Schema::table('rh_employes', fn (Blueprint $table) => $table->dropColumn(['qr_token', 'qr_genere_le']));
    }
};
