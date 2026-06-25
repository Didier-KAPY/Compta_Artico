<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etat_besoin_lignes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('etat_besoin_id')
                ->constrained('etat_besoins')
                ->onDelete('cascade');

            $table->string('designation');
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 15, 2);
            $table->decimal('montant', 15, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etat_besoin_lignes');
    }
};