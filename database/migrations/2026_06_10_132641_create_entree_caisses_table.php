<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entree_caisses', function (Blueprint $table) {

            $table->id();

            // 👤 utilisateur créateur
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // 🔢 numéro unique du document
            $table->string('numero')->unique();

            // 📅 date de l'entrée
            $table->date('date');

            // 📝 infos principales
            $table->string('motif');

            // 💰 montant
            $table->decimal('montant', 15, 2)->default(0);

            // 💱 monnaie
            $table->enum('monnaie', ['CDF', 'USD'])->default('CDF');

            // 📌 statut workflow
            $table->enum('statut', ['En attente', 'Validé', 'Rejeté'])
                ->default('En attente');
            // 📌 statut workflow
            $table->enum('type', ['Caisse', 'Banque', 'Mobile Money'])
                ->default('Caisse');

            // 🧾 observation
            $table->text('observation')->nullable();
            $table->text('date_validation')->nullable();

            $table->string('valide_par')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entree_caisses');
    }
};