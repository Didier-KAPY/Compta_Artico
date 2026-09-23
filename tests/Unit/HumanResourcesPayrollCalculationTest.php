<?php
namespace Tests\Unit;
use App\Models\RhPaie;
use PHPUnit\Framework\TestCase;
class HumanResourcesPayrollCalculationTest extends TestCase {
 public function test_salary_advance_is_deducted_once_even_when_other_deductions_are_disabled():void{$p=new RhPaie(['salaire_base'=>200,'transport'=>40,'telecommunication'=>10,'avance_salaire'=>30,'retenues'=>20,'total_taxes'=>10,'appliquer_retenues'=>false,'appliquer_retenue_absence'=>false]);$this->assertSame(250.0,$p->calculerTotalGains());$this->assertSame(220.0,$p->net);$p->appliquer_retenues=true;$this->assertSame(190.0,$p->net);}
 public function test_net_uses_all_deductions():void{$p=new RhPaie(['salaire_base'=>1000,'primes'=>150,'retenues'=>75,'total_taxes'=>100,'total_cotisations'=>25,'salaire_net'=>0]);$this->assertSame(1150.0,$p->calculerTotalGains());$this->assertSame(950.0,$p->net);}
 public function test_zero_stored_net_does_not_hide_calculation():void{$p=new RhPaie(['salaire_base'=>200,'primes'=>0,'retenues'=>0,'salaire_net'=>'0.00']);$this->assertSame(200.0,$p->net);}
}
