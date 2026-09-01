<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('rh_types_contrats',function(Blueprint $t){$t->id();$t->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();$t->string('code',30);$t->string('libelle');$t->boolean('date_fin_obligatoire')->default(false);$t->boolean('actif')->default(true);$t->timestamps();$t->unique(['entreprise_id','code']);});
  Schema::table('rh_contrats',fn(Blueprint $t)=>$t->foreignId('type_contrat_id')->nullable()->after('employe_id')->constrained('rh_types_contrats')->nullOnDelete());
  Schema::create('rh_avenants_contrats',function(Blueprint $t){$t->id();$t->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();$t->foreignId('contrat_id')->constrained('rh_contrats')->restrictOnDelete();$t->string('numero');$t->date('date_effet');$t->text('motif');$t->json('anciennes_valeurs');$t->json('nouvelles_valeurs');$t->string('document_signe')->nullable();$t->string('statut',30)->default('Brouillon');$t->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();$t->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();$t->timestamp('valide_le')->nullable();$t->timestamps();$t->softDeletes();$t->unique(['entreprise_id','numero']);});
 }
 public function down():void {Schema::dropIfExists('rh_avenants_contrats');Schema::table('rh_contrats',fn(Blueprint $t)=>$t->dropConstrainedForeignId('type_contrat_id'));Schema::dropIfExists('rh_types_contrats');}
};
