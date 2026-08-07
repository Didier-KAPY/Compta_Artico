<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brc_journal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brc_id')->constrained('brcs')->restrictOnDelete();
            $table->foreignId('journal_id')->constrained('journaux')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['brc_id', 'journal_id']);
        });

        DB::table('brcs')->whereNotNull('journal_id')->orderBy('id')->each(function ($brc) {
            DB::table('brc_journal')->insertOrIgnore([
                'brc_id' => $brc->id,
                'journal_id' => $brc->journal_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brc_journal');
    }
};
