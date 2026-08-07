<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature')->nullable()->after('photo');
        });

        Schema::table('entreprises', function (Blueprint $table) {
            $table->string('cachet')->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature');
        });

        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn('cachet');
        });
    }
};
