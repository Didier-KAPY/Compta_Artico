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
        Schema::create('sortie_caisses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('numero')->unique();
            $table->date('date');

            $table->foreignId('etat_besoin_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('beneficiaire');
            $table->string('motif');

            $table->decimal('montant', 15, 2);

            // ✅ MONNAIE (CDF / USD)
            $table->enum('monnaie', ['CDF', 'USD'])->default('CDF');

            // ✅ STATUT (workflow)
            $table->enum('statut', ['En attente', 'Validé', 'Rejeté'])
                  ->default('En attente');

            $table->enum('type', ['Caisse', 'Banque', 'Mobile Money'])
                ->default('Caisse');

            // 🧾 observation
            $table->text('observation')->nullable();
            $table->text('date_validation')->nullable();

            $table->string('valide_par')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sortie_caisses');
    }
};