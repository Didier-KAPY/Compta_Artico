<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brcs', function (Blueprint $table) {
            $table->string('piece_justificative')->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('brcs', function (Blueprint $table) {
            $table->dropColumn('piece_justificative');
        });
    }
};

