<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entree_caisses', function (Blueprint $table) {
            if (! Schema::hasColumn('entree_caisses', 'nom_partenaire')) {
                $table->string('nom_partenaire')->nullable()->after('motif');
            }
            if (! Schema::hasColumn('entree_caisses', 'telephone_partenaire')) {
                $table->string('telephone_partenaire', 50)->nullable()->after('nom_partenaire');
            }
            if (! Schema::hasColumn('entree_caisses', 'adresse_partenaire')) {
                $table->string('adresse_partenaire')->nullable()->after('telephone_partenaire');
            }
        });
    }

    public function down(): void
    {
        Schema::table('entree_caisses', function (Blueprint $table) {
            $columns = collect(['nom_partenaire', 'telephone_partenaire', 'adresse_partenaire'])
                ->filter(fn (string $column) => Schema::hasColumn('entree_caisses', $column))
                ->all();
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};