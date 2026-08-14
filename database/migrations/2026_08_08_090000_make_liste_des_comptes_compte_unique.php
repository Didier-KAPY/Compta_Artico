<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('liste_des_comptes', function (Blueprint $table) {
            $table->dropIndex(['compte']);
            $table->unique('compte');
        });
    }

    public function down(): void
    {
        Schema::table('liste_des_comptes', function (Blueprint $table) {
            $table->dropUnique(['compte']);
            $table->index('compte');
        });
    }
};
