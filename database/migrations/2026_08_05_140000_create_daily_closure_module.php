<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clotures_journalieres')) Schema::create('clotures_journalieres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id')->default(0);
            $table->string('numero_cloture')->unique();
            $table->date('date_comptable');
            $table->unsignedSmallInteger('revision')->default(1);
            $table->string('type_cloture', 20)->default('principale');
            $table->string('statut', 20)->default('preparation');
            $table->decimal('total_recettes_cdf', 18, 2)->default(0);
            $table->decimal('total_recettes_usd', 18, 2)->default(0);
            $table->decimal('total_depenses_cdf', 18, 2)->default(0);
            $table->decimal('total_depenses_usd', 18, 2)->default(0);
            $table->decimal('total_od_cdf', 18, 2)->default(0);
            $table->decimal('total_od_usd', 18, 2)->default(0);
            $table->unsignedInteger('total_journaux')->default(0);
            $table->unsignedInteger('total_ecritures')->default(0);
            $table->unsignedInteger('nombre_journaux_rejetes')->default(0);
            $table->boolean('est_verifiee')->default(false);
            $table->foreignId('ouverte_par')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cloturee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verifiee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_cloture')->nullable();
            $table->timestamp('verifiee_le')->nullable();
            $table->text('motif_complement')->nullable();
            $table->text('motif_reouverture')->nullable();
            $table->timestamps();
            $table->unique(['entreprise_id', 'date_comptable', 'revision'], 'clotures_entreprise_date_revision_unique');
        });

        foreach (['entree_caisses', 'sortie_caisses', 'brcs'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'origine')) Schema::table($tableName, fn (Blueprint $table) => $table->string('origine', 20)->default('manuel')->index());
            if (! Schema::hasColumn($tableName, 'cloture_journaliere_id')) Schema::table($tableName, fn (Blueprint $table) => $table->foreignId('cloture_journaliere_id')->nullable()->constrained('clotures_journalieres')->restrictOnDelete());
            if (! Schema::hasColumn($tableName, 'genere_automatiquement_le')) Schema::table($tableName, fn (Blueprint $table) => $table->timestamp('genere_automatiquement_le')->nullable());
        }

        if (! Schema::hasColumn('journaux', 'statut_regroupement')) Schema::table('journaux', fn (Blueprint $table) => $table->string('statut_regroupement', 20)->default('non_regroupe')->index());
        if (! Schema::hasColumn('journaux', 'cloture_journaliere_id')) Schema::table('journaux', fn (Blueprint $table) => $table->foreignId('cloture_journaliere_id')->nullable()->constrained('clotures_journalieres')->restrictOnDelete());
        if (! Schema::hasColumn('journaux', 'regroupe_le')) Schema::table('journaux', fn (Blueprint $table) => $table->timestamp('regroupe_le')->nullable());

        if (! Schema::hasTable('sortie_caisse_lignes')) Schema::create('sortie_caisse_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sortie_caisse_id')->constrained('sortie_caisses')->cascadeOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('journaux')->restrictOnDelete();
            $table->string('designation');
            $table->decimal('quantite', 15, 2)->default(1);
            $table->decimal('prix_unitaire', 18, 2)->default(0);
            $table->decimal('montant', 18, 2)->default(0);
            $table->timestamps();
        });

        if (! Schema::hasTable('cloture_journaliere_journaux')) Schema::create('cloture_journaliere_journaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloture_journaliere_id')->constrained('clotures_journalieres')->restrictOnDelete();
            $table->foreignId('journal_id')->constrained('journaux')->restrictOnDelete()->unique();
            $table->string('categorie_document', 10);
            $table->string('type_tresorerie', 20);
            $table->foreignId('entree_caisse_id')->nullable()->constrained('entree_caisses')->restrictOnDelete();
            $table->foreignId('sortie_caisse_id')->nullable()->constrained('sortie_caisses')->restrictOnDelete();
            $table->foreignId('brc_id')->nullable()->constrained('brcs')->restrictOnDelete();
            $table->timestamp('regroupe_le');
            $table->timestamps();
        });

        // Les relations historiques restent détectées par leurs clés étrangères
        // et la table brc_journal. Leur classement peut être repris par lots,
        // sans bloquer le déploiement de cette migration structurelle.
    }

    public function down(): void
    {
        Schema::dropIfExists('cloture_journaliere_journaux');
        Schema::dropIfExists('sortie_caisse_lignes');
        Schema::table('journaux', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cloture_journaliere_id');
            $table->dropColumn(['statut_regroupement', 'regroupe_le']);
        });
        foreach (['entree_caisses', 'sortie_caisses', 'brcs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('cloture_journaliere_id');
                $table->dropColumn(['origine', 'genere_automatiquement_le']);
            });
        }
        Schema::dropIfExists('clotures_journalieres');
    }
};
