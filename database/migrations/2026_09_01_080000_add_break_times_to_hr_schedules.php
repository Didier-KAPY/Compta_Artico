<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::table('rh_horaires',function(Blueprint $t){$t->time('heure_debut_pause')->nullable()->after('heure_debut');$t->time('heure_fin_pause')->nullable()->after('heure_debut_pause');});}public function down():void{Schema::table('rh_horaires',fn(Blueprint $t)=>$t->dropColumn(['heure_debut_pause','heure_fin_pause']));}};
