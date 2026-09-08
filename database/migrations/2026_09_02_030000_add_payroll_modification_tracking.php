<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::table('rh_paies',function(Blueprint $t){$t->foreignId('modifie_par')->nullable()->constrained('users')->nullOnDelete();$t->timestamp('modifie_le')->nullable();$t->text('motif_modification')->nullable();});}public function down():void{Schema::table('rh_paies',function(Blueprint $t){$t->dropConstrainedForeignId('modifie_par');$t->dropColumn(['modifie_le','motif_modification']);});}};
