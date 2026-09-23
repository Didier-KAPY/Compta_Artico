<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('constatations_comptables')) {
            Schema::create('constatations_comptables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
                $table->foreignId('source_ecriture_id')->unique()->constrained('ecritures_comptables')->restrictOnDelete();
                $table->foreignId('reglement_journal_id')->unique()->constrained('journaux')->restrictOnDelete();
                $table->foreignId('compte_liaison_id')->constrained('liste_des_comptes')->restrictOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->string('numero')->unique();
                $table->string('piece_origine');
                $table->string('type_operation', 20);
                $table->date('date');
                $table->decimal('montant_cdf', 18, 2);
                $table->decimal('montant_usd', 18, 2)->default(0);
                $table->decimal('taux_change', 20, 8)->nullable();
                $table->string('statut', 20)->default('Validée');
                $table->json('instantane_source');
                $table->timestamps();
            });
        }

        Schema::table('ecritures_comptables', function (Blueprint $table) {
            if (! Schema::hasColumn('ecritures_comptables', 'constatation_id')) {
                $table->foreignId('constatation_id')->nullable()->constrained('constatations_comptables')->restrictOnDelete();
            }
            if (! Schema::hasColumn('ecritures_comptables', 'role_constatation')) {
                $table->string('role_constatation', 20)->nullable();
            }
            if (! Schema::hasColumn('ecritures_comptables', 'nature_constatation')) {
                $table->string('nature_constatation', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Migration de réparation : aucune suppression automatique de données.
    }
};
