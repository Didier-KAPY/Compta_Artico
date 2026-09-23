<?php

namespace Tests\Feature;

use App\Models\{ConstatationComptable, EcritureComptable, EntreeCaisse, Entreprise, JournalType, Journaux, ListeDesComptes, PeriodeComptable, Role, SortieCaisse, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConstatationComptableTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $role = Role::create(['designation'=>'Comptable']);
        $user = User::create(['nom'=>'Test', 'prenom'=>'Comptable', 'email'=>'constatation@test.local', 'password'=>bcrypt('password'), 'role_id'=>$role->id, 'password_default'=>0, 'statut'=>'Actif']);
        $company = Entreprise::create(['user_id'=>$user->id, 'nom_entreprise'=>'Test']);
        $this->actingAs($user);
        $accounts = [];
        foreach (['55221','661','422','447','431'] as $code) $accounts[] = ListeDesComptes::create(['user_id'=>$user->id, 'compte'=>$code, 'designation'=>$code, 'nature'=>'Actif']);
        $type = JournalType::create(['user_id'=>$user->id, 'code'=>'MOB', 'libelle'=>'Mobile', 'nature'=>'mobile_money', 'est_tresorerie'=>true, 'liste_des_comptes_id'=>$accounts[0]->id]);
        $bon = SortieCaisse::create(['user_id'=>$user->id, 'numero'=>'BSM-TEST', 'type_bon'=>'BSM', 'date'=>'2026-08-31', 'beneficiaire'=>'Agents', 'mode_paiement'=>'Mobile money', 'type'=>'depense', 'motif'=>'Salaire', 'montant'=>760, 'monnaie'=>'USD', 'statut'=>'Validé']);
        $journal = Journaux::create(['user_id'=>$user->id, 'journal_type_id'=>$type->id, 'liste_des_comptes_id'=>$accounts[0]->id, 'sortie_caisse_id'=>$bon->id, 'reference'=>'BSM-TEST', 'date'=>'2026-08-31', 'type'=>'depense', 'monnaie'=>'USD', 'statut'=>'Validé', 'sorties_usd'=>760]);
        $source = EcritureComptable::create(['user_id'=>$user->id, 'journal_id'=>$journal->id, 'liste_des_comptes_id'=>$accounts[0]->id, 'date'=>'2026-08-31', 'piece'=>'BSM-TEST', 'libelle'=>'Paiement salaire', 'debit_cdf'=>0, 'credit_cdf'=>1748000, 'statut'=>'En attente']);
        return [$user, $company, $source, $accounts];
    }

    private function payload(array $accounts, bool $retentions = false): array
    {
        $line = fn($account, $nature, $d, $c)=>['liste_des_comptes_id'=>$account->id, 'nature'=>$nature, 'libelle'=>'Salaire', 'debit_cdf'=>$d, 'credit_cdf'=>$c];
        $lines = [$line($accounts[1], 'charge', $retentions ? 1900000 : 1748000, 0), $line($accounts[2], 'dette', 0, 1748000)];
        if ($retentions) { $lines[]=$line($accounts[3], 'retenue', 0, 100000); $lines[]=$line($accounts[4], 'retenue', 0, 52000); }
        return ['date'=>'2026-08-31', 'type_operation'=>'salaire', 'compte_liaison_id'=>$accounts[2]->id, 'lignes'=>$lines];
    }

    public function test_salary_with_multiple_retentions_completes_payment_without_duplicating_treasury(): void
    {
        [$user,$company,$source,$accounts]=$this->context();
        $this->get(route('ecritures.show',$source))->assertOk()->assertSee('Constater');
        $this->get(route('ecritures.constatation',$source))->assertOk()->assertSee('2 300,00000000');
        $data=$this->payload($accounts,true);
        $this->post(route('ecritures.constatation.store',$source),$data)->assertRedirect()->assertSessionHasNoErrors();
        $record=ConstatationComptable::firstOrFail();
        $this->assertSame(4,$record->lignes()->count());
        $this->assertEquals(1900000,$record->lignes()->sum('debit_cdf'));
        $this->assertEquals(1900000,$record->lignes()->sum('credit_cdf'));
        $this->assertEquals(1748000,$record->reglement()->sum('debit_cdf'));
        $this->assertEquals(1748000,$record->reglement()->sum('credit_cdf'));
        $this->assertSame(1,EcritureComptable::where('liste_des_comptes_id',$accounts[0]->id)->count());
        $this->assertSame('Validé',$source->fresh()->statut);
        $this->assertDatabaseHas('audit_logs',['action'=>'constatation_comptable', 'model_id'=>$record->id]);
        $this->get(route('ecritures.show',$source))->assertOk()->assertSee('Historique de la constatation')->assertSee('Voir la constatation');
        $this->get(route('ecritures.constatation',$source))->assertOk()->assertSee($record->numero);
        $this->get(route('ecritures.show',$record->lignes()->first()))->assertOk();
        $this->post(route('ecritures.constatation.store',$source),$data)->assertSessionHasErrors('constatation');
        $this->assertSame(1,ConstatationComptable::count());
        $this->assertSame(6,EcritureComptable::count());
    }

    public function test_salary_without_retention_is_supported(): void
    {
        [$u,$c,$source,$accounts]=$this->context();
        $this->post(route('ecritures.constatation.store',$source),$this->payload($accounts))->assertSessionHasNoErrors();
        $this->assertSame(2,ConstatationComptable::firstOrFail()->lignes()->count());
    }

    public function test_list_shows_constatation_before_newer_payment_and_preserves_status_filter(): void
    {
        [$u,$c,$source,$accounts]=$this->context();
        $data=$this->payload($accounts);
        $data['date']='2026-08-01';
        $this->post(route('ecritures.constatation.store',$source),$data)->assertSessionHasNoErrors();
        $record=ConstatationComptable::firstOrFail();
        $expected=$record->lignes()->pluck('id')
            ->concat($record->reglement()->pluck('id'))->all();
        $this->get(route('ecritures.liste',['statut'=>'']))->assertOk()
            ->assertViewHas('statut', '')
            ->assertViewHas('ecritures',fn($rows)=>$rows->pluck('id')->all() === $expected);
        $this->get(route('ecritures.constatation',$source))->assertOk()
            ->assertViewHas('lignesExistantes',fn($rows)=>(float)$rows->first()->debit_cdf > 0 && (float)$rows->last()->credit_cdf > 0);
        $this->get(route('ecritures.liste',['statut'=>'En attente']))->assertOk()
            ->assertViewHas('ecritures',fn($rows)=>$rows->total() === 0);
        $this->get(route('ecritures.liste'))->assertOk()
            ->assertViewHas('statut', 'En attente')
            ->assertViewHas('ecritures',fn($rows)=>$rows->total() === 0);
    }

    public function test_invalid_balance_net_or_accounts_leave_no_partial_entries(): void
    {
        [$u,$c,$source,$accounts]=$this->context();
        $data=$this->payload($accounts);
        $data['lignes'][0]['debit_cdf']=1900000;
        $this->post(route('ecritures.constatation.store',$source),$data)->assertSessionHasErrors('constatation');
        $data['lignes'][1]['credit_cdf']=1900000;
        $this->post(route('ecritures.constatation.store',$source),$data)->assertSessionHasErrors('constatation');
        $data=$this->payload($accounts);
        $data['lignes'][0]['liste_des_comptes_id']=$accounts[0]->id;
        $this->post(route('ecritures.constatation.store',$source),$data)->assertSessionHasErrors('constatation');
        $this->assertSame(0,ConstatationComptable::count());
        $this->assertSame(1,EcritureComptable::count());
        $this->assertSame('En attente',$source->fresh()->statut);
    }

    public function test_closed_source_period_and_closed_constatation_period_are_rejected(): void
    {
        [$u,$c,$source,$accounts]=$this->context();
        $period=PeriodeComptable::create(['type'=>'mensuelle','date_debut'=>'2026-08-01','date_fin'=>'2026-08-31','statut'=>'fermee']);
        $data=$this->payload($accounts); $data['date']='2026-09-18';
        $this->post(route('ecritures.constatation.store',$source),$data)->assertSessionHasErrors('date');
        $period->update(['date_debut'=>'2026-09-01','date_fin'=>'2026-09-30']);
        $this->post(route('ecritures.constatation.store',$source),$data)->assertSessionHasErrors('date');
        $this->assertSame(0,ConstatationComptable::count());
    }

    public function test_foreign_company_source_and_account_are_rejected(): void
    {
        [$u,$c,$source,$accounts]=$this->context();
        $other=Entreprise::create(['user_id'=>$u->id,'nom_entreprise'=>'Other']);
        $foreign=ListeDesComptes::create(['entreprise_id'=>$other->id,'user_id'=>$u->id,'compte'=>'669','designation'=>'Foreign','nature'=>'Actif']);
        $data=$this->payload($accounts); $data['lignes'][0]['liste_des_comptes_id']=$foreign->id;
        $this->post(route('ecritures.constatation.store',$source),$data)->assertSessionHasErrors('constatation');
        $source->update(['entreprise_id'=>$other->id]);
        $this->get(route('ecritures.constatation',$source))->assertNotFound();
        $this->post(route('ecritures.constatation.store',$source),$this->payload($accounts))->assertNotFound();
        $this->assertSame(0,ConstatationComptable::count());
    }

    public function test_admin_cannot_record_and_validated_payment_cannot_be_modified(): void
    {
        [$u,$c,$source,$accounts]=$this->context();
        $role=Role::create(['designation'=>'Admin']); $u->update(['role_id'=>$role->id]); $u->unsetRelation('role');
        $this->post(route('ecritures.constatation.store',$source),$this->payload($accounts))->assertForbidden();
        $u->update(['role_id'=>Role::where('designation','Comptable')->value('id')]); $u->unsetRelation('role');
        $this->post(route('ecritures.constatation.store',$source),$this->payload($accounts))->assertSessionHasNoErrors();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $source->fresh()->update(['credit_cdf'=>1]);
    }

    public function test_generic_product_constatation_completes_an_incoming_payment(): void
    {
        [$u,$c,$source,$accounts]=$this->context();
        $receipt=EntreeCaisse::create(['user_id'=>$u->id,'numero'=>'BEM-TEST','date'=>'2026-08-31','motif'=>'Vente','nom_partenaire'=>'Client','montant'=>760,'monnaie'=>'USD','statut'=>'Validé']);
        $source->journal->update(['sortie_caisse_id'=>null,'entree_caisse_id'=>$receipt->id,'type'=>'recette']);
        $source->update(['debit_cdf'=>1748000,'credit_cdf'=>0]);
        $product=ListeDesComptes::create(['user_id'=>$u->id,'compte'=>'701','designation'=>'Ventes','nature'=>'Produit']);
        $data=$this->payload($accounts);
        $data['type_operation']='generique';
        $data['lignes'][0]=['liste_des_comptes_id'=>$accounts[2]->id,'nature'=>'autre','libelle'=>'Créance client','debit_cdf'=>1748000,'credit_cdf'=>0];
        $data['lignes'][1]=['liste_des_comptes_id'=>$product->id,'nature'=>'autre','libelle'=>'Vente','debit_cdf'=>0,'credit_cdf'=>1748000];
        $this->post(route('ecritures.constatation.store',$source),$data)->assertSessionHasNoErrors();
        $record=ConstatationComptable::firstOrFail();
        $this->assertDatabaseHas('ecritures_comptables',['constatation_id'=>$record->id,'role_constatation'=>'reglement','liste_des_comptes_id'=>$accounts[2]->id,'credit_cdf'=>1748000]);
        $this->assertEquals(1748000,$record->reglement()->sum('debit_cdf'));
        $this->assertEquals(1748000,$record->reglement()->sum('credit_cdf'));
    }

    public function test_failure_during_audit_rolls_back_both_accounting_groups(): void
    {
        [$u,$c,$source,$accounts]=$this->context();
        $this->mock(\App\Services\AuditLogService::class, function($mock) {
            $mock->shouldReceive('record')->once()->andThrow(new \RuntimeException('Audit unavailable'));
        });
        $request=\Illuminate\Http\Request::create('/', 'POST');
        $request->setUserResolver(fn()=>$u);
        try {
            app(\App\Services\ConstatationComptableService::class)->store($source->id,$this->payload($accounts,true),$request);
            $this->fail('Expected audit failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit unavailable',$e->getMessage());
        }
        $this->assertSame(0,ConstatationComptable::count());
        $this->assertSame(1,EcritureComptable::count());
        $this->assertSame('En attente',$source->fresh()->statut);
        $this->assertNull($source->fresh()->constatation_id);
    }
}
