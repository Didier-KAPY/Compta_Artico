<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lignes_budgetaires', function (Blueprint $table) {
            $table->date('date_debut')->nullable()->after('description');
            $table->date('date_fin')->nullable()->after('date_debut');
            $table->index(['date_debut', 'date_fin'], 'lignes_budgetaires_periode_index');
        });
    }

    public function down(): void
    {
        Schema::table('lignes_budgetaires', function (Blueprint $table) {
            $table->dropIndex('lignes_budgetaires_periode_index');
            $table->dropColumn(['date_debut', 'date_fin']);
        });
    }
};
