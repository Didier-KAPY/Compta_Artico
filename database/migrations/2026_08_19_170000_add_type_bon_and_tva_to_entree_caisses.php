<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entree_caisses', function (Blueprint $table): void {
            $table->string('type_bon', 3)->default('BEC')->after('numero');
            $table->boolean('appliquer_tva')->default(false)->after('montant');
            $table->decimal('taux_tva', 5, 2)->default(0)->after('appliquer_tva');
            $table->decimal('montant_ht', 15, 2)->default(0)->after('taux_tva');
            $table->decimal('montant_tva', 15, 2)->default(0)->after('montant_ht');
        });
    }

    public function down(): void
    {
        Schema::table('entree_caisses', function (Blueprint $table): void {
            $table->dropColumn(['type_bon', 'appliquer_tva', 'taux_tva', 'montant_ht', 'montant_tva']);
        });
    }
};