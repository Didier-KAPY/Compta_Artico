<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journaux', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['journal_type_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('journal_type_id')->references('id')->on('journal_types')->restrictOnDelete();
        });

        Schema::table('ecritures_comptables', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['journal_id']);
            $table->dropForeign(['liste_des_comptes_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('journal_id')->references('id')->on('journaux')->restrictOnDelete();
            $table->foreign('liste_des_comptes_id')->references('id')->on('liste_des_comptes')->restrictOnDelete();
        });

        Schema::table('brcs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
        Schema::table('etat_besoin_lignes', function (Blueprint $table) {
            $table->dropForeign(['etat_besoin_id']);
            $table->foreign('etat_besoin_id')->references('id')->on('etat_besoins')->restrictOnDelete();
        });
        Schema::table('entree_caisse_lignes', function (Blueprint $table) {
            $table->dropForeign(['entree_caisse_id']);
            $table->foreign('entree_caisse_id')->references('id')->on('entree_caisses')->restrictOnDelete();
        });
        Schema::table('ligne_brcs', function (Blueprint $table) {
            $table->dropForeign(['brc_id']);
            $table->foreign('brc_id')->references('id')->on('brcs')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journaux', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['journal_type_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('journal_type_id')->references('id')->on('journal_types')->cascadeOnDelete();
        });
        Schema::table('ecritures_comptables', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['journal_id']);
            $table->dropForeign(['liste_des_comptes_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('journal_id')->references('id')->on('journaux')->cascadeOnDelete();
            $table->foreign('liste_des_comptes_id')->references('id')->on('liste_des_comptes')->cascadeOnDelete();
        });
        Schema::table('brcs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
        Schema::table('etat_besoin_lignes', function (Blueprint $table) {
            $table->dropForeign(['etat_besoin_id']);
            $table->foreign('etat_besoin_id')->references('id')->on('etat_besoins')->cascadeOnDelete();
        });
        Schema::table('entree_caisse_lignes', function (Blueprint $table) {
            $table->dropForeign(['entree_caisse_id']);
            $table->foreign('entree_caisse_id')->references('id')->on('entree_caisses')->cascadeOnDelete();
        });
        Schema::table('ligne_brcs', function (Blueprint $table) {
            $table->dropForeign(['brc_id']);
            $table->foreign('brc_id')->references('id')->on('brcs')->cascadeOnDelete();
        });
    }
};
