<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sortie_caisses', function (Blueprint $table) {
            $table->string('numero')->nullable()->change();
            $table->string('type_bon', 3)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sortie_caisses', function (Blueprint $table) {
            $table->string('type_bon', 3)->default('BSC')->nullable(false)->change();
            $table->string('numero')->nullable(false)->change();
        });
    }
};