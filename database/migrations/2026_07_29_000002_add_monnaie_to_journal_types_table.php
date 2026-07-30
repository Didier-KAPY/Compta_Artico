<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_types', function (Blueprint $table) {
            $table->enum('monnaie', ['CDF', 'USD'])->default('CDF')->after('nature');
        });

        DB::table('journal_types')
            ->where(function ($query) {
                $query->whereRaw('UPPER(code) LIKE ?', ['%USD%'])
                    ->orWhereRaw('UPPER(libelle) LIKE ?', ['%USD%']);
            })
            ->update(['monnaie' => 'USD']);
    }

    public function down(): void
    {
        Schema::table('journal_types', function (Blueprint $table) {
            $table->dropColumn('monnaie');
        });
    }
};
