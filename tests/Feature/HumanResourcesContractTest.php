<?php
namespace Tests\Feature;
use App\Models\{Employe,Entreprise,RhContrat,Role,User};
use App\Services\RhContratService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class HumanResourcesContractTest extends TestCase {use RefreshDatabase;
 public function test_employee_exists_without_user_and_contract_uses_employee():void{$a=$this->user();$ent=Entreprise::create(['user_id'=>$a->id,'nom_entreprise'=>'ARTICO']);$e=Employe::create(['entreprise_id'=>$ent->id,'matricule'=>'EMP-001','nom'=>'Sans compte','statut'=>'Actif']);$c=app(RhContratService::class)->creer($this->data($e),$a->id);$this->assertNull($e->user_id);$this->assertSame($e->id,$c->employe_id);}
 public function test_cdd_without_end_date_is_refused_by_request_rules():void{$a=$this->user();$ent=Entreprise::create(['user_id'=>$a->id,'nom_entreprise'=>'ARTICO']);$e=Employe::create(['entreprise_id'=>$ent->id,'matricule'=>'EMP-002','nom'=>'Test','statut'=>'Actif']);$this->actingAs($a)->post(route('parametres.rh.contrats.store'),array_merge($this->data($e),['type'=>'CDD','date_fin'=>null]))->assertSessionHasErrors('date_fin');}
 public function test_overlapping_active_contract_is_refused():void{$a=$this->user();$ent=Entreprise::create(['user_id'=>$a->id,'nom_entreprise'=>'ARTICO']);$e=Employe::create(['entreprise_id'=>$ent->id,'matricule'=>'EMP-003','nom'=>'Test','statut'=>'Actif']);$s=app(RhContratService::class);$s->creer($this->data($e),$a->id);$this->expectException(ValidationException::class);$s->creer(array_merge($this->data($e),['numero'=>'C-002']),$a->id);}
 private function data(Employe $e):array{return ['employe_id'=>$e->id,'numero'=>'C-001','type'=>'CDI','date_debut'=>'2026-01-01','date_fin'=>null,'periode_essai_jours'=>0,'heures_hebdomadaires'=>40,'salaire_base'=>1000,'devise'=>'USD','statut'=>'Actif'];}
 private function user():User{$role=Role::firstOrCreate(['designation'=>'Super Admin']);return User::create(['nom'=>'Admin','prenom'=>'RH','email'=>uniqid().'@test.local','password'=>bcrypt('password'),'role_id'=>$role->id,'statut'=>'Actif','password_default'=>false]);}
}
