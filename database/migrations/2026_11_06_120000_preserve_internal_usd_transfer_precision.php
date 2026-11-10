<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journaux', function (Blueprint $table) {
            $table->decimal('entrees_usd', 38, 18)->default(0)->change();
        });
    }

    public function down(): void
    {
        // Preserve precision in stored USD transfers instead of truncating values.
    }
};
