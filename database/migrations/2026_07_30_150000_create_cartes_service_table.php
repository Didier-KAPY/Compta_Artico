<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartes_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('numero')->unique();
            $table->string('postnom')->nullable();
            $table->string('adresse')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('sexe', 20)->nullable();
            $table->date('date_delivrance');
            $table->string('nom_signataire');
            $table->timestamps();

            $table->index(['user_id', 'date_delivrance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartes_service');
    }
};
