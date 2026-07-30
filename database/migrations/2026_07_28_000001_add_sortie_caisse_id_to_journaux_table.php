<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('journaux', 'sortie_caisse_id')) {
            Schema::table('journaux', function (Blueprint $table) {
                $table->foreignId('sortie_caisse_id')->nullable()
                    ->after('entree_caisse_id')->constrained('sortie_caisses')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('journaux', 'sortie_caisse_id')) {
            Schema::table('journaux', function (Blueprint $table) {
                $table->dropConstrainedForeignId('sortie_caisse_id');
            });
        }
    }
};
