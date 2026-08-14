<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
            $table->foreignId('liste_des_comptes_id')->constrained('liste_des_comptes')->restrictOnDelete();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->decimal('montant_prevu', 18, 2);
            $table->string('monnaie', 3)->default('CDF');
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['departement_id', 'liste_des_comptes_id', 'date_debut', 'date_fin', 'monnaie'], 'budgets_perimetre_unique');
        });
    }

    public function down(): void { Schema::dropIfExists('budgets'); }
};
