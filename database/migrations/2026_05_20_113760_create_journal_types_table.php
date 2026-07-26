<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

public function up(): void
{


Schema::create('journal_types', function(Blueprint $table){


$table->id();


$table->foreignId('user_id')
      ->constrained()
      ->cascadeOnDelete();



/*
Code Sage :
CAI
BQ
ACH
VTE
OD
*/

$table->string('code',20);



/*
Nom du journal
Journal Caisse CDF
*/

$table->string('libelle');



/*
Compte lié :
571100
521100
532100
*/

$table->foreignId('liste_des_comptes_id')
      ->nullable()
      ->constrained('liste_des_comptes')
      ->nullOnDelete();



/*
Nature du journal
*/

$table->enum('nature',[

'caisse',
'banque',
'mobile_money',
'achat',
'vente',
'od'

])->default('od');



/*
Journal de trésorerie ?
*/

$table->boolean('est_tresorerie')
      ->default(false);



$table->timestamps();


});


}



public function down(): void
{
Schema::dropIfExists('journal_types');
}

};