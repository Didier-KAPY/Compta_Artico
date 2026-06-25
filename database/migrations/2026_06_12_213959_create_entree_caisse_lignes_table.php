<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entree_caisse_lignes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entree_caisse_id')
                ->constrained('entree_caisses')
                ->onDelete('cascade');

            $table->string('designation');
            $table->unsignedInteger('quantite')->default(1);
            $table->decimal('prix_unitaire', 12, 2)->default(0);
            $table->decimal('montant', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entree_caisse_lignes');
    }
};