<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('rubriques_budgetaires')) {
            Schema::create('rubriques_budgetaires', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('designation');
                $table->string('nature', 10)->index();
                $table->foreignId('liste_des_comptes_id')->constrained('liste_des_comptes')->restrictOnDelete();
                $table->text('description')->nullable();
                $table->boolean('actif')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['nature','liste_des_comptes_id'], 'rubriques_nature_compte_unique');
            });
        }

        if (! Schema::hasColumn('lignes_budgetaires', 'rubrique_budgetaire_id')) {
            Schema::table('lignes_budgetaires', function (Blueprint $table) {
                $table->foreignId('rubrique_budgetaire_id')->nullable()->after('liste_des_comptes_id')
                    ->constrained('rubriques_budgetaires')->restrictOnDelete();
            });
        }

        DB::table('lignes_budgetaires')->whereNull('rubrique_budgetaire_id')->orderBy('id')->get()->each(function ($ligne) {
            $compte = DB::table('liste_des_comptes')->where('id', $ligne->liste_des_comptes_id)->first();
            $nature = mb_strtolower(trim((string) ($compte->nature ?? ''))) === 'produit' ? 'RECETTE' : 'DEPENSE';
            $rubriqueId = DB::table('rubriques_budgetaires')->where('nature', $nature)
                ->where('liste_des_comptes_id', $ligne->liste_des_comptes_id)->value('id');
            if (! $rubriqueId) {
                $rubriqueId = DB::table('rubriques_budgetaires')->insertGetId([
                    'code' => 'RUB-'.str_pad((string) $ligne->id, 5, '0', STR_PAD_LEFT),
                    'designation' => $ligne->rubrique,
                    'nature' => $nature,
                    'liste_des_comptes_id' => $ligne->liste_des_comptes_id,
                    'actif' => true,
                    'created_by' => $ligne->created_by,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('lignes_budgetaires')->where('id', $ligne->id)->update(['rubrique_budgetaire_id' => $rubriqueId]);
        });

        Schema::table('ecritures_comptables', function (Blueprint $table) {
            $table->index(['liste_des_comptes_id','statut','date'], 'ecritures_budget_execution_index');
        });
    }

    public function down(): void
    {
        Schema::table('ecritures_comptables', fn (Blueprint $table) => $table->dropIndex('ecritures_budget_execution_index'));
        Schema::table('lignes_budgetaires', fn (Blueprint $table) => $table->dropConstrainedForeignId('rubrique_budgetaire_id'));
        Schema::dropIfExists('rubriques_budgetaires');
    }
};
