<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {

            $table->id();

            $table->string('numero_facture')->unique();

            $table->string('nom_client');
            $table->string('contact_client')->nullable();

            $table->foreignId('id_user')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('montant_total', 15, 2)->default(0);
            $table->decimal('montant_paye', 15, 2)->default(0);
            $table->decimal('reste_a_payer', 15, 2)->default(0);

            $table->unsignedBigInteger('numero_compte')->nullable();

            $table->enum('statut', [
                'en_attente',
                'partiel',
                'paye',
                'annule'
            ])->default('en_attente');

            $table->date('date_facture');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};