<?php
namespace Tests\Unit;
use App\Models\{Employe,Entreprise,RhPaie,Role,User};
use App\Services\RhPaymentReferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class HumanResourcesPaymentReferenceTest extends TestCase {use RefreshDatabase;
 public function test_reference_is_sequential_by_company_and_period():void{$r=Role::firstOrCreate(['designation'=>'Super Admin']);$u=User::create(['nom'=>'A','prenom'=>'B','email'=>'ref@test.local','password'=>bcrypt('x'),'role_id'=>$r->id,'statut'=>'Actif']);$ent=Entreprise::create(['user_id'=>$u->id,'nom_entreprise'=>'ARTICO']);$e=Employe::create(['entreprise_id'=>$ent->id,'matricule'=>'EMP-1','nom'=>'Test','statut'=>'Actif']);RhPaie::create(['entreprise_id'=>$ent->id,'employe_id'=>$e->id,'annee'=>2026,'mois'=>8,'salaire_base'=>100,'monnaie'=>'CDF','statut'=>'Payée','reference_paiement'=>'PAY-202608-000001']);$s=app(RhPaymentReferenceService::class);$this->assertSame('PAY-202608-000002',$s->generate($ent->id,2026,8));$this->assertSame('PAY-202609-000001',$s->generate($ent->id,2026,9));}
}
