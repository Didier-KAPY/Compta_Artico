<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bilan_initials', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('libelle');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->decimal('total_actif', 20, 2)->default(0);
            $table->decimal('total_passif', 20, 2)->default(0);
            $table->decimal('ecart', 20, 2)->default(0);
            $table->json('donnees');
        });
    }

    public function down(): void
    {
        Schema::table('bilan_initials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['libelle', 'date_debut', 'date_fin', 'total_actif', 'total_passif', 'ecart', 'donnees']);
        });
    }
};