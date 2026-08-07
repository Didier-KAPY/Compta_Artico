<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ecritures_comptables', 'piece_justificative')) {
            return;
        }
        Schema::table('ecritures_comptables', function (Blueprint $table) {
            $table->string('piece_justificative')->nullable()->after('piece');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ecritures_comptables', 'piece_justificative')) {
            return;
        }
        Schema::table('ecritures_comptables', function (Blueprint $table) {
            $table->dropColumn('piece_justificative');
        });
    }
};
