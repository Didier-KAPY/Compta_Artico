<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('entreprises', 'slogan')) {
            return;
        }

        Schema::table('entreprises', function (Blueprint $table) {
            $table->string('slogan')->nullable()->after('nom_entreprise');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('entreprises', 'slogan')) {
            return;
        }

        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn('slogan');
        });
    }
};
