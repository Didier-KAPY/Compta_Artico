<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('journal_type_id')->constrained('journal_types')->restrictOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('journaux')->nullOnDelete();
            $table->string('reference')->nullable()->unique();
            $table->date('date');
            $table->enum('monnaie', ['CDF', 'USD'])->default('CDF');
            $table->enum('sens', ['debit', 'credit']);
            $table->decimal('total', 18, 2)->default(0);
            $table->enum('statut', ['En attente', 'Validé', 'Rejeté'])->default('En attente');
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_validation')->nullable();
            $table->timestamps();
        });

        Schema::create('ligne_brcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brc_id')->constrained('brcs')->cascadeOnDelete();
            $table->foreignId('liste_des_comptes_id')->constrained('liste_des_comptes')->restrictOnDelete();
            $table->string('libelle');
            $table->decimal('montant', 18, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_brcs');
        Schema::dropIfExists('brcs');
    }
};
