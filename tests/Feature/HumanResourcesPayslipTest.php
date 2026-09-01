<?php
namespace Tests\Feature;
use App\Models\{AuditLog,Employe,Entreprise,RhPaie,Role,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class HumanResourcesPayslipTest extends TestCase {use RefreshDatabase;
 public function test_authorized_user_downloads_audited_pdf_payslip():void{$u=$this->user();$ent=Entreprise::create(['user_id'=>$u->id,'nom_entreprise'=>'ARTICO','monnaie_budgetaire'=>'CDF']);$e=Employe::create(['entreprise_id'=>$ent->id,'matricule'=>'EMP-001','nom'=>'Mwamba','prenom'=>'Aline','statut'=>'Actif']);$p=RhPaie::create(['entreprise_id'=>$ent->id,'employe_id'=>$e->id,'annee'=>2026,'mois'=>8,'salaire_base'=>1000,'primes'=>100,'retenues'=>50,'monnaie'=>'USD','statut'=>'Validée']);$this->actingAs($u)->get(route('parametres.rh.paie.bulletin',$p))->assertOk()->assertHeader('content-type','application/pdf');$this->assertDatabaseHas('audit_logs',['action'=>'telechargement_bulletin_rh','model_id'=>$p->id]);}
 public function test_payslip_from_another_company_is_hidden():void{$u=$this->user();Entreprise::create(['user_id'=>$u->id,'nom_entreprise'=>'ARTICO']);$owner=$this->user('Admin');$other=Entreprise::create(['user_id'=>$owner->id,'nom_entreprise'=>'AUTRE']);$e=Employe::create(['entreprise_id'=>$other->id,'matricule'=>'EMP-X','nom'=>'Autre','statut'=>'Actif']);$p=RhPaie::create(['entreprise_id'=>$other->id,'employe_id'=>$e->id,'annee'=>2026,'mois'=>8,'salaire_base'=>100,'monnaie'=>'CDF','statut'=>'Brouillon']);$this->actingAs($u)->get(route('parametres.rh.paie.bulletin',$p))->assertNotFound();}
 private function user(string $role='Super Admin'):User{$r=Role::firstOrCreate(['designation'=>$role]);return User::create(['nom'=>'Test','prenom'=>$role,'email'=>uniqid().'@test.local','password'=>bcrypt('password'),'role_id'=>$r->id,'statut'=>'Actif','password_default'=>false]);}
}
