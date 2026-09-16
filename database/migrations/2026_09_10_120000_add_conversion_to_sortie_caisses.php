<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sortie_caisses', function (Blueprint $table) {
            $table->decimal('taux_conversion', 18, 6)->nullable();
            $table->date('date_taux_conversion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sortie_caisses', fn (Blueprint $table) => $table->dropColumn(['taux_conversion', 'date_taux_conversion']));
    }
};
