<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['journaux', 'liste_des_comptes', 'ecritures_comptables'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->foreignId('entreprise_id')->nullable()->constrained('entreprises')->restrictOnDelete());
        }
        // Legacy ownership is unambiguous for a single-company installation.
        $companies = DB::table('entreprises')->get(['id', 'user_id']);
        foreach (['journaux', 'liste_des_comptes', 'ecritures_comptables'] as $name) {
            if ($companies->count() === 1) {
                DB::table($name)->update(['entreprise_id'=>$companies->first()->id]);
            } else {
                foreach ($companies->groupBy('user_id') as $userId => $owned) {
                    if ($owned->count() === 1) DB::table($name)->where('user_id', $userId)->update(['entreprise_id'=>$owned->first()->id]);
                }
            }
        }
        Schema::create('constatations_comptables', function (Blueprint $t) {
            $t->id();
            $t->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $t->foreignId('source_ecriture_id')->unique()->constrained('ecritures_comptables')->restrictOnDelete();
            $t->foreignId('reglement_journal_id')->unique()->constrained('journaux')->restrictOnDelete();
            $t->foreignId('compte_liaison_id')->constrained('liste_des_comptes')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->string('numero')->unique();
            $t->string('piece_origine');
            $t->string('type_operation', 20);
            $t->date('date');
            $t->decimal('montant_cdf', 18, 2);
            $t->decimal('montant_usd', 18, 2)->default(0);
            $t->decimal('taux_change', 20, 8)->nullable();
            $t->string('statut', 20)->default('Validée');
            $t->json('instantane_source');
            $t->timestamps();
        });
        Schema::table('ecritures_comptables', function (Blueprint $t) {
            $t->unsignedBigInteger('journal_id')->nullable()->change();
            $t->foreignId('constatation_id')->nullable()->constrained('constatations_comptables')->restrictOnDelete();
            $t->string('role_constatation', 20)->nullable();
            $t->string('nature_constatation', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ecritures_comptables', function (Blueprint $t) {
            $t->dropConstrainedForeignId('constatation_id');
            $t->dropColumn(['role_constatation', 'nature_constatation']);
        });
        Schema::dropIfExists('constatations_comptables');
        foreach (['ecritures_comptables', 'liste_des_comptes', 'journaux'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->dropConstrainedForeignId('entreprise_id'));
        }
    }
};
