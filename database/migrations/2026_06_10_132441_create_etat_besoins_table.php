<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('etat_besoins', function (Blueprint $table) {
            $table->id();

            // Utilisateur ayant créé l'état de besoin
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Informations générales
            $table->string('numero')->unique();
            $table->date('date');
            $table->string('service');
            $table->string('demandeur');
            $table->text('motif');

            // Montant total calculé à partir des lignes
            $table->decimal('montant_estime', 15, 2)->default(0);

            // Devise
            $table->enum('monnaie', ['CDF', 'USD'])->default('CDF');

            // Statut du document
            $table->enum('statut', [
                'En attente',
                'Validé',
                'Rejeté'
            ])->default('En attente');

            // Observations éventuelles
            $table->text('observation')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etat_besoins');
    }
};