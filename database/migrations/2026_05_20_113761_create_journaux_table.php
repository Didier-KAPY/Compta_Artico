<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journaux', function (Blueprint $table) {

            $table->id();

            // Utilisateur
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Type de journal
            $table->foreignId('journal_type_id')
                ->constrained('journal_types')
                ->cascadeOnDelete();

            // Compte comptable
            $table->foreignId('liste_des_comptes_id')
                ->nullable()
                ->constrained('liste_des_comptes')
                ->nullOnDelete();

            // Entrée de caisse
            // IMPORTANT : pas de clé étrangère ici,
            // car la table entree_caisses est créée après.
            $table->unsignedBigInteger('entree_caisse_id')->nullable();

            // Référence
            $table->string('reference')->index();

            // Date
            $table->date('date');

            // Partenaire
            $table->string('nom_partenaire')->nullable();
            $table->string('telephone_partenaire')->nullable();
            $table->string('adresse_partenaire')->nullable();

            // Description
            $table->text('description')->nullable();

            // Pièce justificative
            $table->string('piece_justificatif')->nullable();

            // Type
            $table->enum('type', [
                'recette',
                'depense',
                'achat',
                'vente',
                'od'
            ])->default('od');

            // Monnaie
            $table->enum('monnaie', [
                'CDF',
                'USD'
            ])->default('CDF');

            // Paiement
            $table->enum('mode_paiement', [
                'espèces',
                'banque',
                'mobile_money'
            ])->default('espèces');

            // Montants
            $table->decimal('montant_ht', 15, 2)->default(0);
            $table->decimal('taux_tva', 5, 2)->default(0);
            $table->decimal('montant_tva', 15, 2)->default(0);
            $table->decimal('montant_ttc', 15, 2)->default(0);

            // Trésorerie
            $table->decimal('entrees_cdf', 15, 2)->default(0);
            $table->decimal('sorties_cdf', 15, 2)->default(0);
            $table->decimal('entrees_usd', 15, 2)->default(0);
            $table->decimal('sorties_usd', 15, 2)->default(0);

            // Statut
            $table->enum('statut', [
                'En attente',
                'Validé',
                'Rejeté'
            ])->default('En attente');

            // Validation
            $table->timestamp('date_validation')->nullable();

            $table->foreignId('valide_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journaux');
    }
};
