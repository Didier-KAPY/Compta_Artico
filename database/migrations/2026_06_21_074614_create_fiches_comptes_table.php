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
        Schema::create('fiches_comptes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liste_des_comptes_id')
                  ->nullable()
                  ->change();
            $table->foreignId('journal_type_id')
                  ->nullable()
                  ->change();
            $table->string('input')->nullable();
            $table->date('date_du_jour');
            $table->string('piece_justificatif')->nullable();
            $table->string('libelle_explicatif')->nullable();
            $table->decimal('montant_debit', 15, 2)->nullable();
            $table->decimal('montant_credit', 15, 2)->nullable();
            $table->foreignId('id_user')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiches_comptes');
    }
};
