<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        

        // Table users
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();

            // Relation avec roles
            $table->foreignId('role_id')
                  ->constrained('roles')
                  ->cascadeOnDelete();

            $table->string('telephone')->nullable();
            $table->string('password');

            $table->string('adresse')->nullable();
            $table->string('photo')->nullable();

            $table->timestamp('email_verified_at')->nullable();

            // Dernière connexion de l'utilisateur
            $table->timestamp('last_logged_in')->nullable();

            // Indique si le mot de passe est encore celui par défaut
            $table->tinyInteger('password_default')
                  ->default(0);

            $table->enum('statut', ['Actif', 'Inactif'])
                  ->default('Actif');

            $table->rememberToken();

            $table->timestamps();
        });


        // Table password reset
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });


        // Table sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};