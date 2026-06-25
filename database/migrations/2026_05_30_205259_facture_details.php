<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facture_details', function (Blueprint $table) {

            $table->id();

            $table->foreignId('facture_id')
                ->constrained('factures')
                ->cascadeOnDelete();

            $table->string('libelle');

            $table->integer('quantite')->default(1);

            $table->decimal('prix_unitaire', 15, 2);

            $table->decimal('montant_ligne', 15, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_details');
    }
};