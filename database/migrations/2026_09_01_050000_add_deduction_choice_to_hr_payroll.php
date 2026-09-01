<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::table('rh_paies',fn(Blueprint $t)=>$t->boolean('appliquer_retenues')->default(true)->after('retenues'));}public function down():void{Schema::table('rh_paies',fn(Blueprint $t)=>$t->dropColumn('appliquer_retenues'));}};
