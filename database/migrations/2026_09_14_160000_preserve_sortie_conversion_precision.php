<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['sortie_caisses' => ['montant', 'montant_ht', 'montant_tva'], 'journaux' => ['montant_ht', 'montant_tva', 'montant_ttc', 'sorties_cdf', 'sorties_usd']] as $name => $fields) {
            Schema::table($name, function (Blueprint $table) use ($fields) {
                foreach ($fields as $field) $table->decimal($field, 38, 18)->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        // Keep the expanded precision: reducing it would destroy converted decimals.
    }
};
