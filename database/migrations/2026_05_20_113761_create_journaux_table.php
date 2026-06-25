<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journaux', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('liste_des_comptes_id')
                  ->nullable()
                  ->change();
            $table->foreignId('input')
                  ->nullable()
                  ->change();
            $table->foreignId('entree_caisse_id')
                  ->nullable()
                  ->change();
            $table->foreignId('sortie_caisse_id')
                  ->nullable()
                  ->change();
            $table->foreignId('journal_type_id')
                  ->nullable()
                  ->change();

            $table->foreignId('taux_de_change_id')
                ->nullable()
                ->constrained('taux_de_changes')
                ->nullOnDelete();

            $table->date('date');

            $table->string('reference')->nullable();

            $table->text('description')->nullable();

            $table->string('piece_justificatif')->nullable();

            // ✅ 3 modes de paiement
            $table->enum('mode_paiement', ['espece', 'banque', 'mobile_money'])
                ->default('espece');
            $table->enum('monnaie', ['CDF', 'USD'])->default('CDF');
            // Montants
            $table->decimal('entrees_cdf', 15, 2)->default(0);
            $table->decimal('sorties_cdf', 15, 2)->default(0);
            $table->decimal('entrees_usd', 15, 2)->default(0);
            $table->decimal('sorties_usd', 15, 2)->default(0);
            $table->decimal('total_entrees_cdf', 15, 2)->default(0);
            $table->decimal('total_sorties_cdf', 15, 2)->default(0);

            // ✅ statut
            // ✅ STATUT (workflow)
            $table->enum('statut', ['En attente', 'Validé', 'Rejeté'])
                  ->default('En attente');
            $table->enum('type', ['Caisse', 'Banque', 'Monnaie électronique'])
                ->default('Caisse');
            $table->text('date_validation')->nullable();

            $table->string('valide_par')->nullable();
                

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('journaux', function (Blueprint $table) {
            $table->foreignId('liste_des_comptes_id')
                  ->nullable(false)
                  ->change();

            $table->foreignId('journal_type_id')
                  ->nullable(false)
                  ->change();
        });
    }
};