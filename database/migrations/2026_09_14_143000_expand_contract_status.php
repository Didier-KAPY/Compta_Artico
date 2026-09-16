<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rh_contrats', function (Blueprint $table) {
            $table->string('statut', 50)->default('Actif')->change();
        });
    }

    public function down(): void
    {
        Schema::table('rh_contrats', function (Blueprint $table) {
            $table->string('statut', 20)->default('Actif')->change();
        });
    }
};
