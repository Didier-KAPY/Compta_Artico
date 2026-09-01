<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Support\Facades\DB;
return new class extends Migration {public function up():void{DB::table('rh_horaires')->where('code','STANDARD')->update(['code'=>'PRINCIPAL','libelle'=>'Horaire principal','updated_at'=>now()]);}public function down():void{DB::table('rh_horaires')->where('code','PRINCIPAL')->update(['code'=>'STANDARD','libelle'=>'Horaire standard','updated_at'=>now()]);}};
