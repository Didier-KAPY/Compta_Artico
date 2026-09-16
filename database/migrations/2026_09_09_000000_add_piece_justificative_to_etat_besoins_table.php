<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etat_besoins', function (Blueprint $table) {
            $table->string('piece_justificative')->nullable()->after('observation');
            $table->string('piece_justificative_nom')->nullable()->after('piece_justificative');
        });
    }

    public function down(): void
    {
        Schema::table('etat_besoins', function (Blueprint $table) {
            $table->dropColumn(['piece_justificative', 'piece_justificative_nom']);
        });
    }
};
