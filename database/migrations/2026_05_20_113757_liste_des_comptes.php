<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

public function up(): void
{

Schema::create('liste_des_comptes', function (Blueprint $table) {

    $table->id();


    $table->foreignId('user_id')
          ->constrained()
          ->cascadeOnDelete();


    // Exemple : 571100
    $table->string('compte',20)
          ->index();


    // Exemple : Caisse CDF
    $table->string('designation');


    // Actif, Passif, Charge...
    $table->string('nature')
          ->nullable();


    $table->text('observation')
          ->nullable();


    $table->timestamps();

});

}



public function down(): void
{
Schema::dropIfExists('liste_des_comptes');
}

};