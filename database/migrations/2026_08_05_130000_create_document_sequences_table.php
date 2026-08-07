<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id')->default(0);
            $table->string('type_document', 20);
            $table->string('type_tresorerie', 30)->default('');
            $table->unsignedSmallInteger('annee');
            $table->unsignedTinyInteger('mois');
            $table->unsignedInteger('dernier_numero')->default(0);
            $table->timestamps();

            $table->unique(
                ['entreprise_id', 'type_document', 'type_tresorerie', 'annee', 'mois'],
                'document_sequences_scope_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
