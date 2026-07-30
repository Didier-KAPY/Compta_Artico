<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fonctions', function (Blueprint $table) {
            $table->id();
            $table->string('designation')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('fonction_id')->nullable()->after('departement_id')
                ->constrained('fonctions')->nullOnDelete();
        });

        DB::table('departements')->whereNotNull('fonction')->orderBy('id')->get()
            ->each(function ($departement) {
                $designation = trim((string) $departement->fonction);
                if ($designation === '') {
                    return;
                }

                $fonction = DB::table('fonctions')->where('designation', $designation)->first();
                $fonctionId = $fonction?->id ?: DB::table('fonctions')->insertGetId([
                    'designation' => $designation,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('users')->where('departement_id', $departement->id)
                    ->whereNull('fonction_id')->update(['fonction_id' => $fonctionId]);
            });

        Schema::table('departements', function (Blueprint $table) {
            $table->dropColumn('fonction');
        });
    }

    public function down(): void
    {
        Schema::table('departements', function (Blueprint $table) {
            $table->string('fonction')->nullable()->after('designation');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fonction_id');
        });

        Schema::dropIfExists('fonctions');
    }
};
