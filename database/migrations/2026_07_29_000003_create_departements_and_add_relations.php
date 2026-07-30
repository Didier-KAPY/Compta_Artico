<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departements', function (Blueprint $table) {
            $table->id();
            $table->string('designation')->unique();
            $table->string('fonction')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('departement_id')->nullable()->after('role_id')
                ->constrained('departements')->nullOnDelete();
        });

        Schema::table('etat_besoins', function (Blueprint $table) {
            $table->foreignId('departement_id')->nullable()->after('user_id')
                ->constrained('departements')->nullOnDelete();
        });

        DB::table('etat_besoins')->select('service')->whereNotNull('service')->distinct()
            ->orderBy('service')->each(function ($etat) {
                $designation = trim((string) $etat->service);
                if ($designation === '') return;
                $id = DB::table('departements')->insertGetId([
                    'designation' => $designation, 'fonction' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('etat_besoins')->where('service', $etat->service)->update(['departement_id' => $id]);
            });
    }

    public function down(): void
    {
        Schema::table('etat_besoins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('departement_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('departement_id');
        });
        Schema::dropIfExists('departements');
    }
};
