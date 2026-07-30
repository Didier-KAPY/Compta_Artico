<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etat_besoins', function (Blueprint $table) {
            if (! Schema::hasColumn('etat_besoins', 'valide_par')) {
                $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('etat_besoins', 'date_validation')) {
                $table->timestamp('date_validation')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('etat_besoins', function (Blueprint $table) {
            if (Schema::hasColumn('etat_besoins', 'valide_par')) {
                $table->dropConstrainedForeignId('valide_par');
            }
            if (Schema::hasColumn('etat_besoins', 'date_validation')) {
                $table->dropColumn('date_validation');
            }
        });
    }
};
