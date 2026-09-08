<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rh_contrats', function (Blueprint $t) {
            $t->string('mode_remuneration', 30)->default('Mensuel');
            $t->json('jours_travail')->nullable();
            $t->time('heure_debut_prevue')->nullable();
            $t->time('heure_fin_prevue')->nullable();
            $t->decimal('primes_fixes', 18, 2)->default(0);
            $t->decimal('indemnites_fixes', 18, 2)->default(0);
            $t->decimal('jours_reference', 6, 2)->default(26);
            $t->decimal('heures_reference', 8, 2)->default(173.33);
        });

        Schema::table('rh_presences', function (Blueprint $t) {
            $t->time('heure_debut_prevue')->nullable();
            $t->time('heure_fin_prevue')->nullable();
            $t->boolean('heures_supplementaires_approuvees')->default(false);
            $t->decimal('heures_supplementaires_validees', 6, 2)->default(0);
            $t->text('motif_correction')->nullable();
            $t->json('valeurs_avant_correction')->nullable();
        });

        Schema::create('rh_syntheses_mensuelles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $t->foreignId('employe_id')->constrained('rh_employes')->restrictOnDelete();
            $t->unsignedSmallInteger('annee');
            $t->unsignedTinyInteger('mois');
            $t->unsignedSmallInteger('jours_ouvrables_prevus')->default(0);
            $t->unsignedSmallInteger('jours_presents')->default(0);
            $t->decimal('jours_conges_payes', 6, 2)->default(0);
            $t->decimal('jours_mission', 6, 2)->default(0);
            $t->decimal('jours_absence_non_remuneree', 6, 2)->default(0);
            $t->unsignedSmallInteger('nombre_retards')->default(0);
            $t->unsignedInteger('retard_total_minutes')->default(0);
            $t->decimal('heures_supplementaires_validees', 8, 2)->default(0);
            $t->string('statut', 30)->default('Brouillon');
            $t->foreignId('generee_par')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('generee_le')->nullable();
            $t->foreignId('validee_par')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('validee_le')->nullable();
            $t->timestamp('verrouillee_le')->nullable();
            $t->timestamps();
            $t->unique(['employe_id', 'annee', 'mois'], 'rh_synthese_employe_periode_unique');
        });

        Schema::table('rh_paies', function (Blueprint $t) {
            $t->foreignId('contrat_id')->nullable()->constrained('rh_contrats')->nullOnDelete();
            $t->foreignId('synthese_mensuelle_id')->nullable()->constrained('rh_syntheses_mensuelles')->nullOnDelete();
            $t->decimal('retenue_absence_theorique', 18, 2)->default(0);
            $t->boolean('appliquer_retenue_absence')->default(true);
            $t->text('motif_non_retenue_absence')->nullable();
            $t->foreignId('decision_retenue_par')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decision_retenue_le')->nullable();
            $t->decimal('heures_supplementaires', 8, 2)->default(0);
            $t->decimal('montant_heures_supplementaires', 18, 2)->default(0);
            $t->json('instantane_calcul')->nullable();
            $t->timestamp('bulletin_genere_le')->nullable();
            $t->timestamp('cloture_le')->nullable();
        });

        Schema::create('rh_paiements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $t->foreignId('paie_id')->constrained('rh_paies')->restrictOnDelete();
            $t->foreignId('employe_id')->constrained('rh_employes')->restrictOnDelete();
            $t->decimal('montant_net_a_payer', 18, 2);
            $t->decimal('montant_paye', 18, 2);
            $t->string('devise', 3);
            $t->string('mode_paiement', 40);
            $t->date('date_paiement');
            $t->string('reference_paiement')->nullable();
            $t->string('compte_paiement')->nullable();
            $t->text('observation')->nullable();
            $t->foreignId('paye_par')->nullable()->constrained('users')->nullOnDelete();
            $t->string('statut', 30)->default('Payé');
            $t->timestamps();
            $t->index(['paie_id', 'statut']);
        });

        Schema::create('rh_workflow_historiques', function (Blueprint $t) {
            $t->id();
            $t->foreignId('entreprise_id')->nullable()->constrained('entreprises')->nullOnDelete();
            $t->morphs('workflowable');
            $t->string('action', 80);
            $t->string('ancien_statut', 40)->nullable();
            $t->string('nouveau_statut', 40)->nullable();
            $t->text('motif')->nullable();
            $t->json('ancienne_valeur')->nullable();
            $t->json('nouvelle_valeur')->nullable();
            $t->foreignId('effectue_par')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rh_workflow_historiques');
        Schema::dropIfExists('rh_paiements');
        Schema::table('rh_paies', fn (Blueprint $t) => $t->dropColumn(['contrat_id','synthese_mensuelle_id','retenue_absence_theorique','appliquer_retenue_absence','motif_non_retenue_absence','decision_retenue_par','decision_retenue_le','heures_supplementaires','montant_heures_supplementaires','instantane_calcul','bulletin_genere_le','cloture_le']));
        Schema::dropIfExists('rh_syntheses_mensuelles');
        Schema::table('rh_presences', fn (Blueprint $t) => $t->dropColumn(['heure_debut_prevue','heure_fin_prevue','heures_supplementaires_approuvees','heures_supplementaires_validees','motif_correction','valeurs_avant_correction']));
        Schema::table('rh_contrats', fn (Blueprint $t) => $t->dropColumn(['mode_remuneration','jours_travail','heure_debut_prevue','heure_fin_prevue','primes_fixes','indemnites_fixes','jours_reference','heures_reference']));
    }
};
