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
        Schema::create('parametres_comptables', function(Blueprint $table){

    $table->id();
    $table->foreignId('user_id')
      ->constrained()
      ->cascadeOnDelete();
    $table->string('code');

    $table->string('designation');

    $table->foreignId('liste_des_comptes_id')
          ->constrained('liste_des_comptes')
          ->cascadeOnDelete();

    $table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametres_comptables');
    }
};
