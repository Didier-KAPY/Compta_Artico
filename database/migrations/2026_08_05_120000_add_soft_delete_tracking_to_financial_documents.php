<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'etat_besoins', 'sortie_caisses', 'entree_caisses',
        'journaux', 'ecritures_comptables', 'brcs',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->softDeletes();
                $table->text('motif_suppression')->nullable();
                $table->foreignId('supprime_par')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('restaure_par')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('restaure_le')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('restaure_par');
                $table->dropConstrainedForeignId('supprime_par');
                $table->dropColumn(['motif_suppression', 'restaure_le', 'deleted_at']);
            });
        }
    }
};
