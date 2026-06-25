<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {

            $table->id();

            $table->foreignId('facture_id')
                ->constrained('factures')
                ->cascadeOnDelete();

            // Caisse CDF, Banque USD, Mobile Money CDF...
            $table->foreignId('liste_des_comptes_id')
                ->constrained('liste_des_comptes')
                ->restrictOnDelete();

            $table->foreignId('id_user')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('montant', 15, 2);

            $table->enum('mode_paiement', [
                'cash',
                'banque',
                'mobile_money'
            ]);

            $table->dateTime('date_paiement');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};