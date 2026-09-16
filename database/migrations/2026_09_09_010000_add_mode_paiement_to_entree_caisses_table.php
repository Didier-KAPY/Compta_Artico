<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('entree_caisses', 'mode_paiement')) {
            Schema::table('entree_caisses', function (Blueprint $table) {
                $table->string('mode_paiement', 30)->nullable()->after('adresse_partenaire');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('entree_caisses', 'mode_paiement')) {
            Schema::table('entree_caisses', function (Blueprint $table) {
                $table->dropColumn('mode_paiement');
            });
        }
    }
};
