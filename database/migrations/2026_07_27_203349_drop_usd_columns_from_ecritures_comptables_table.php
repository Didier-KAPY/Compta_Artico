<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = array_values(array_filter(
            ['debit_usd', 'credit_usd'],
            fn (string $column): bool => Schema::hasColumn('ecritures_comptables', $column)
        ));

        if ($columns !== []) {
            Schema::table('ecritures_comptables', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        Schema::table('ecritures_comptables', function (Blueprint $table) {
            if (! Schema::hasColumn('ecritures_comptables', 'debit_usd')) {
                $table->decimal('debit_usd', 18, 2)->default(0);
            }

            if (! Schema::hasColumn('ecritures_comptables', 'credit_usd')) {
                $table->decimal('credit_usd', 18, 2)->default(0);
            }
        });
    }
};