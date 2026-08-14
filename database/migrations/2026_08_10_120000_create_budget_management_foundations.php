<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL applique les DDL immédiatement. Si un processus est interrompu
        // après la dernière création mais avant l'inscription dans `migrations`,
        // la présence de cette table prouve que toute la séquence est terminée.
        if (Schema::hasTable('mouvements_budgetaires')) {
            return;
        }

        Schema::table('entreprises', function (Blueprint $table) {
            $table->string('monnaie_budgetaire', 3)->default('CDF')->after('numero_identification_fiscal');
        });
        Schema::table('taux_de_changes', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->nullable()->after('user_id')->constrained('entreprises')->nullOnDelete();
            $table->string('devise_source', 3)->default('USD')->after('entreprise_id');
            $table->string('devise_cible', 3)->default('CDF')->after('devise_source');
            $table->date('date_taux')->nullable()->after('taux_de_change')->index();
        });
        Schema::create('budget_exercices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->unsignedSmallInteger('exercice');
            $table->string('libelle');
            $table->string('monnaie', 3);
            $table->decimal('montant_initial', 18, 2)->default(0);
            $table->string('statut', 30)->default('Brouillon');
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_validation')->nullable();
            $table->foreignId('cloture_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_cloture')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['entreprise_id', 'exercice']);
        });
        Schema::create('lignes_budgetaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_exercice_id')->constrained('budget_exercices')->restrictOnDelete();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
            $table->foreignId('liste_des_comptes_id')->constrained('liste_des_comptes')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('rubrique');
            $table->text('description')->nullable();
            $table->decimal('prevision_initiale', 18, 2)->default(0);
            $table->decimal('revisions_positives', 18, 2)->default(0);
            $table->decimal('revisions_negatives', 18, 2)->default(0);
            $table->decimal('engagements_actifs', 18, 2)->default(0);
            $table->decimal('realisations', 18, 2)->default(0);
            $table->string('statut', 30)->default('Active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['budget_exercice_id', 'code']);
        });
        Schema::create('mensualites_budgetaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ligne_budgetaire_id')->constrained('lignes_budgetaires')->cascadeOnDelete();
            $table->unsignedTinyInteger('mois');
            $table->decimal('montant', 18, 2);
            $table->string('mode_repartition', 30)->default('manuelle');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['ligne_budgetaire_id', 'mois']);
        });
        Schema::table('etat_besoins', function (Blueprint $table) {
            $table->foreignId('ligne_budgetaire_id')->nullable()->after('departement_id')->constrained('lignes_budgetaires')->nullOnDelete();
        });
        Schema::create('engagements_budgetaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_exercice_id')->constrained('budget_exercices')->restrictOnDelete();
            $table->foreignId('ligne_budgetaire_id')->constrained('lignes_budgetaires')->restrictOnDelete();
            $table->foreignId('etat_besoin_id')->unique()->constrained('etat_besoins')->restrictOnDelete();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->decimal('montant_original', 18, 2);
            $table->string('monnaie_originale', 3);
            $table->decimal('taux_change', 18, 6)->default(1);
            $table->date('date_taux');
            $table->decimal('montant_budgetaire', 18, 2);
            $table->decimal('montant_restant', 18, 2);
            $table->decimal('montant_realise', 18, 2)->default(0);
            $table->string('statut', 40)->default('Actif');
            $table->timestamp('date_engagement');
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motif')->nullable();
            $table->timestamps();
        });
        Schema::create('realisations_budgetaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engagement_budgetaire_id')->constrained('engagements_budgetaires')->restrictOnDelete();
            $table->foreignId('sortie_caisse_id')->unique()->constrained('sortie_caisses')->restrictOnDelete();
            $table->decimal('montant_original', 18, 2);
            $table->string('monnaie_originale', 3);
            $table->decimal('taux_change', 18, 6)->default(1);
            $table->decimal('montant_budgetaire', 18, 2);
            $table->timestamp('date_realisation');
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('statut', 30)->default('Validée');
            $table->timestamps();
        });
        Schema::create('mouvements_budgetaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_exercice_id')->constrained('budget_exercices')->restrictOnDelete();
            $table->foreignId('ligne_budgetaire_id')->constrained('lignes_budgetaires')->restrictOnDelete();
            $table->foreignId('engagement_budgetaire_id')->nullable()->constrained('engagements_budgetaires')->nullOnDelete();
            $table->foreignId('realisation_budgetaire_id')->nullable()->constrained('realisations_budgetaires')->nullOnDelete();
            $table->uuid('operation_uuid')->index();
            $table->string('type', 40)->index();
            $table->decimal('montant', 18, 2);
            $table->string('monnaie', 3);
            $table->nullableMorphs('source');
            $table->string('reference_document')->nullable()->index();
            $table->json('ancienne_situation')->nullable();
            $table->json('nouvelle_situation')->nullable();
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_mouvement');
            $table->text('motif')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_budgetaires');
        Schema::dropIfExists('realisations_budgetaires');
        Schema::dropIfExists('engagements_budgetaires');
        Schema::table('etat_besoins', fn (Blueprint $table) => $table->dropConstrainedForeignId('ligne_budgetaire_id'));
        Schema::dropIfExists('mensualites_budgetaires');
        Schema::dropIfExists('lignes_budgetaires');
        Schema::dropIfExists('budget_exercices');
        Schema::table('taux_de_changes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entreprise_id');
            $table->dropColumn(['devise_source', 'devise_cible', 'date_taux']);
        });
        Schema::table('entreprises', fn (Blueprint $table) => $table->dropColumn('monnaie_budgetaire'));
    }
};
