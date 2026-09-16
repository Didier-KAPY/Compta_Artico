<?php
namespace Tests\Feature;
use App\Models\{Employe,Entreprise,RhContrat,Role,User};
use App\Services\RhContratService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class HumanResourcesContractTest extends TestCase {use RefreshDatabase;
 public function test_super_admin_can_validate_own_contract_then_activate_it(): void
 {
     $a = $this->user();
     $ent = Entreprise::create(['user_id' => $a->id, 'nom_entreprise' => 'ARTICO']);
     $e = Employe::create(['entreprise_id' => $ent->id, 'matricule' => 'VALID-001', 'nom' => 'Test', 'statut' => 'Actif']);
     $c = app(RhContratService::class)->creer(array_merge($this->data($e), ['statut' => 'Brouillon']), $a->id);
     $this->actingAs($a)->patch(route('parametres.rh.contrats.submit', $c))->assertSessionHasNoErrors();
     $this->get(route('parametres.rh.contrats.show', $c))->assertOk()->assertSee('Valider le contrat');
     $this->patch(route('parametres.rh.contrats.validate', $c))->assertRedirect()->assertSessionHasNoErrors();
     $this->assertSame('Validé', $c->fresh()->statut);
     $this->assertEquals($a->id, $c->fresh()->valide_par);
     $this->assertNotNull($c->fresh()->valide_le);
     $this->assertDatabaseHas('audit_logs', ['action' => 'validation_contrat_rh', 'model_id' => $c->id]);
     $this->get(route('parametres.rh.contrats.show', $c))->assertOk()->assertSee('Activer le contrat');
     $this->patch(route('parametres.rh.contrats.activate', $c))->assertRedirect()->assertSessionHasNoErrors();
     $this->assertSame('Actif', $c->fresh()->statut);
 }

 public function test_other_creators_cannot_validate_their_own_contract(): void
 {
     $a = $this->user();
     $a->update(['role_id' => Role::firstOrCreate(['designation' => 'Admin'])->id]);
     $ent = Entreprise::create(['user_id' => $a->id, 'nom_entreprise' => 'ARTICO']);
     $e = Employe::create(['entreprise_id' => $ent->id, 'matricule' => 'VALID-002', 'nom' => 'Test', 'statut' => 'Actif']);
     $service = app(RhContratService::class);
     $c = $service->creer(array_merge($this->data($e), ['statut' => 'En attente de validation']), $a->id);
     $this->expectException(ValidationException::class);
     $service->valider($c, $a->id);
 }
 public function test_employee_exists_without_user_and_contract_uses_employee():void{$a=$this->user();$ent=Entreprise::create(['user_id'=>$a->id,'nom_entreprise'=>'ARTICO']);$e=Employe::create(['entreprise_id'=>$ent->id,'matricule'=>'EMP-001','nom'=>'Sans compte','statut'=>'Actif']);$c=app(RhContratService::class)->creer($this->data($e),$a->id);$this->assertNull($e->user_id);$this->assertSame($e->id,$c->employe_id);}
 public function test_cdd_without_end_date_is_refused_by_request_rules():void{$a=$this->user();$ent=Entreprise::create(['user_id'=>$a->id,'nom_entreprise'=>'ARTICO']);$e=Employe::create(['entreprise_id'=>$ent->id,'matricule'=>'EMP-002','nom'=>'Test','statut'=>'Actif']);$this->actingAs($a)->post(route('parametres.rh.contrats.store'),array_merge($this->data($e),['type'=>'CDD','date_fin'=>null]))->assertSessionHasErrors('date_fin');}
 public function test_overlapping_active_contract_is_refused():void{$a=$this->user();$ent=Entreprise::create(['user_id'=>$a->id,'nom_entreprise'=>'ARTICO']);$e=Employe::create(['entreprise_id'=>$ent->id,'matricule'=>'EMP-003','nom'=>'Test','statut'=>'Actif']);$s=app(RhContratService::class);$s->creer($this->data($e),$a->id);$this->expectException(ValidationException::class);$s->creer(array_merge($this->data($e),['numero'=>'C-002']),$a->id);}
 private function data(Employe $e):array{return ['employe_id'=>$e->id,'numero'=>'C-001','type'=>'CDI','date_debut'=>'2026-01-01','date_fin'=>null,'periode_essai_jours'=>0,'heures_hebdomadaires'=>40,'salaire_base'=>1000,'devise'=>'USD','statut'=>'Actif'];}
 private function user():User{$role=Role::firstOrCreate(['designation'=>'Super Admin']);return User::create(['nom'=>'Admin','prenom'=>'RH','email'=>uniqid().'@test.local','password'=>bcrypt('password'),'role_id'=>$role->id,'statut'=>'Actif','password_default'=>false]);}
}
