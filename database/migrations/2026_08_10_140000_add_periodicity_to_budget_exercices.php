<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_exercices', function (Blueprint $table) {
            $table->index('entreprise_id', 'budget_exercices_entreprise_index');
        });

        Schema::table('budget_exercices', function (Blueprint $table) {
            $table->dropUnique(['entreprise_id', 'exercice']);
            $table->string('periodicite', 20)->default('annuel')->after('exercice');
            $table->unsignedTinyInteger('periode_numero')->default(1)->after('periodicite');
            $table->date('date_debut')->nullable()->after('periode_numero');
            $table->date('date_fin')->nullable()->after('date_debut');
            $table->unique(['entreprise_id','exercice','periodicite','periode_numero'], 'budget_exercices_periode_unique');
        });

        DB::table('budget_exercices')->orderBy('id')->get()->each(function ($budget) {
            DB::table('budget_exercices')->where('id', $budget->id)->update([
                'date_debut' => $budget->exercice.'-01-01',
                'date_fin' => $budget->exercice.'-12-31',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('budget_exercices', function (Blueprint $table) {
            $table->dropUnique('budget_exercices_periode_unique');
            $table->dropColumn(['periodicite','periode_numero','date_debut','date_fin']);
            $table->unique(['entreprise_id','exercice']);
            $table->dropIndex('budget_exercices_entreprise_index');
        });
    }
};
