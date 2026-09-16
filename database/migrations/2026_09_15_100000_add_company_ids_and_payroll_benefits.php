<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('entreprises', function (Blueprint $t) {
            $t->string('rccm')->nullable();
            $t->string('id_nat')->nullable();
        });
        Schema::table('rh_paies', function (Blueprint $t) {
            foreach (['transport', 'logement', 'autres_avantages', 'telecommunication'] as $champ) $t->decimal($champ, 18, 2)->default(0);
        });
    }
    public function down(): void {
        Schema::table('entreprises', fn (Blueprint $t) => $t->dropColumn(['rccm','id_nat']));
        Schema::table('rh_paies', fn (Blueprint $t) => $t->dropColumn(['transport','logement','autres_avantages','telecommunication']));
    }
};
