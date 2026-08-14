<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('rh_contrats')) Schema::create('rh_contrats', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); $table->string('numero')->unique();
            $table->date('date_debut'); $table->date('date_fin')->nullable();
            $table->decimal('salaire_base', 18, 2)->default(0); $table->string('statut', 20)->default('Actif');
            $table->text('observations')->nullable(); $table->timestamps();
        });
        if (! Schema::hasTable('rh_presences')) Schema::create('rh_presences', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date'); $table->time('heure_arrivee')->nullable(); $table->time('heure_depart')->nullable();
            $table->string('statut', 20)->default('Présent'); $table->text('observation')->nullable(); $table->timestamps();
            $table->unique(['user_id','date']);
        });
        if (! Schema::hasTable('rh_conges')) Schema::create('rh_conges', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40); $table->date('date_debut'); $table->date('date_fin');
            $table->text('motif')->nullable(); $table->string('statut', 20)->default('En attente');
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('valide_le')->nullable(); $table->timestamps();
        });
        if (! Schema::hasTable('rh_paies')) Schema::create('rh_paies', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('annee'); $table->unsignedTinyInteger('mois');
            $table->decimal('salaire_base',18,2); $table->decimal('primes',18,2)->default(0); $table->decimal('retenues',18,2)->default(0);
            $table->string('monnaie',3)->default('CDF'); $table->string('statut',20)->default('Brouillon'); $table->date('date_paiement')->nullable(); $table->timestamps();
            $table->unique(['user_id','annee','mois']);
        });
        if (! Schema::hasTable('rh_evaluations')) Schema::create('rh_evaluations', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('periode', 50); $table->unsignedTinyInteger('note'); $table->text('objectifs')->nullable(); $table->text('commentaire')->nullable();
            $table->foreignId('evalue_par')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rh_evaluations'); Schema::dropIfExists('rh_paies'); Schema::dropIfExists('rh_conges'); Schema::dropIfExists('rh_presences'); Schema::dropIfExists('rh_contrats');
    }
};
