<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('ecritures_comptables', function (Blueprint $table) {

            $table->id();


            /*
            |--------------------------------------------------------------------------
            | Utilisateur créateur
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();



            /*
            |--------------------------------------------------------------------------
            | Journal
            |--------------------------------------------------------------------------
            */

            $table->foreignId('journal_id')
                ->constrained('journaux')
                ->cascadeOnDelete();



            /*
            |--------------------------------------------------------------------------
            | Compte comptable
            |--------------------------------------------------------------------------
            */

            $table->foreignId('liste_des_comptes_id')
                ->constrained('liste_des_comptes')
                ->cascadeOnDelete();



            /*
            |--------------------------------------------------------------------------
            | Date écriture
            |--------------------------------------------------------------------------
            */

            $table->date('date');



            /*
            |--------------------------------------------------------------------------
            | Pièce et libellé
            |--------------------------------------------------------------------------
            */

            $table->string('piece')
                ->nullable();

            $table->string('piece_justificative')->nullable();


            $table->text('libelle');



            /*
            |--------------------------------------------------------------------------
            | Montants CDF
            |--------------------------------------------------------------------------
            */

            $table->decimal('debit_cdf', 18, 2)
                ->default(0);

            $table->decimal('credit_cdf', 18, 2)
                ->default(0);



            /*
            |--------------------------------------------------------------------------
            | Statut
            |--------------------------------------------------------------------------
            */

            $table->enum('statut', [

                'En attente',
                'Validé',
                'Rejeté'

            ])
            ->default('En attente');



            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $table->date('date_validation')
                ->nullable();



            $table->foreignId('valide_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();



            $table->timestamps();

        });

    }



    public function down(): void
    {
        Schema::dropIfExists('ecritures_comptables');
    }

};
