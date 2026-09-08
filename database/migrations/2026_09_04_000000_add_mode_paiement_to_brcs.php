<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brcs', function (Blueprint $table) {
            $table->string('mode_paiement', 30)->default('mobile_money')->after('sens');
        });
    }

    public function down(): void
    {
        Schema::table('brcs', function (Blueprint $table) {
            $table->dropColumn('mode_paiement');
        });
    }
};
