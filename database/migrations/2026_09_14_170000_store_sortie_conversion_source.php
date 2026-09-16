<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sortie_caisses', function (Blueprint $table) {
            $table->decimal('montant_origine_conversion', 38, 18)->nullable();
            $table->string('monnaie_origine_conversion', 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sortie_caisses', fn (Blueprint $table) => $table->dropColumn(['montant_origine_conversion', 'monnaie_origine_conversion']));
    }
};
