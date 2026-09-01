<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration { public function up():void { DB::table('rh_paies')->orderBy('id')->chunkById(200,function($paies):void{foreach($paies as $p){$g=round((float)$p->salaire_base+(float)$p->primes,2);$n=round($g-(float)$p->retenues-(float)$p->total_taxes-(float)$p->total_cotisations,2);DB::table('rh_paies')->where('id',$p->id)->update(['total_gains'=>$g,'salaire_brut'=>$g,'salaire_imposable'=>$p->salaire_imposable?:$g,'salaire_net'=>$n,'updated_at'=>now()]);}}); } public function down():void {} };
