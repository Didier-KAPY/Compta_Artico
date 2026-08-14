<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('periodes_comptables', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('statut', 15)->default('fermee');
            $table->foreignId('fermee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fermee_le')->nullable();
            $table->foreignId('reouverte_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reouverte_le')->nullable();
            $table->text('motif_reouverture')->nullable();
            $table->timestamps();
            $table->unique(['type', 'date_debut', 'date_fin']);
            $table->index(['statut', 'date_debut', 'date_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodes_comptables');
    }
};
