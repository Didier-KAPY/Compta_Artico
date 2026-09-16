<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['etat_besoins', 'ecritures_comptables'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->json('pieces_justificatives')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['etat_besoins', 'ecritures_comptables'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn('pieces_justificatives'));
        }
    }
};
