<?php

namespace Tests\Feature;

use App\Models\{Employe, Entreprise, RhContrat, RhPaie, RhPeriodePaie, RhSyntheseMensuelle, Role, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HumanResourcesPayslipTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_downloads_audited_pdf_payslip(): void
    {
        $u = $this->user();
        $ent = Entreprise::create(['user_id' => $u->id, 'nom_entreprise' => 'ARTICO', 'monnaie_budgetaire' => 'CDF']);
        $e = Employe::create(['entreprise_id' => $ent->id, 'matricule' => 'EMP-001', 'nom' => 'Mwamba', 'prenom' => 'Aline', 'statut' => 'Actif']);
        $p = RhPaie::create(['entreprise_id' => $ent->id, 'employe_id' => $e->id, 'annee' => 2026, 'mois' => 8, 'salaire_base' => 1000, 'primes' => 100, 'retenues' => 50, 'monnaie' => 'USD', 'statut' => 'Validée']);
        $this->actingAs($u)->get(route('parametres.rh.paie.bulletin', $p))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertDatabaseHas('audit_logs', ['action' => 'telechargement_bulletin_rh', 'model_id' => $p->id]);
    }

    public function test_payslip_from_another_company_is_hidden(): void
    {
        $u = $this->user();
        Entreprise::create(['user_id' => $u->id, 'nom_entreprise' => 'ARTICO']);
        $owner = $this->user('Admin');
        $other = Entreprise::create(['user_id' => $owner->id, 'nom_entreprise' => 'AUTRE']);
        $e = Employe::create(['entreprise_id' => $other->id, 'matricule' => 'EMP-X', 'nom' => 'Autre', 'statut' => 'Actif']);
        $p = RhPaie::create(['entreprise_id' => $other->id, 'employe_id' => $e->id, 'annee' => 2026, 'mois' => 8, 'salaire_base' => 100, 'monnaie' => 'CDF', 'statut' => 'Brouillon']);
        $this->actingAs($u)->get(route('parametres.rh.paie.bulletin', $p))->assertNotFound();
    }

    public function test_super_admin_applique_les_options_a_tous_les_bulletins(): void
    {
        $u = $this->user();
        $ent = Entreprise::create(['user_id' => $u->id, 'nom_entreprise' => 'ARTICO']);
        $periode = RhPeriodePaie::create(['entreprise_id' => $ent->id, 'libelle' => 'Septembre 2026', 'date_debut' => '2026-09-01', 'date_fin' => '2026-09-30', 'annee' => 2026, 'mois' => 9, 'statut' => 'Ouverte', 'cree_par' => $u->id]);

        foreach ([1, 2] as $i) {
            $e = Employe::create(['entreprise_id' => $ent->id, 'matricule' => 'DG-2026-0000'.$i, 'nom' => 'Employé '.$i, 'statut' => 'Actif']);
            RhContrat::create(['entreprise_id' => $ent->id, 'employe_id' => $e->id, 'numero' => 'CTR-2026-0000'.$i, 'type' => 'CDI', 'date_debut' => '2026-01-01', 'salaire_base' => 1000 * $i, 'devise' => 'CDF', 'statut' => 'Actif']);
            RhSyntheseMensuelle::create(['entreprise_id' => $ent->id, 'employe_id' => $e->id, 'periode_paie_id' => $periode->id, 'annee' => 2026, 'mois' => 9, 'jours_ouvrables_prevus' => 22, 'jours_presents' => 20, 'jours_absence_non_remuneree' => 2, 'statut' => 'Validée', 'generee_par' => $u->id, 'validee_par' => $u->id]);
        }
        Employe::create(['entreprise_id' => $ent->id, 'matricule' => 'DG-2026-00999', 'nom' => 'Dossier incomplet', 'statut' => 'Actif']);

        $this->actingAs($u)->post(route('parametres.rh.paie.store-all'), [
            'annee' => 2026,
            'mois' => 9,
            'appliquer_retenues' => 0,
            'appliquer_retenue_absence' => 0,
            'motif_non_retenue_absence' => 'Décision de la direction',
            'transport' => 10, 'logement' => 20, 'autres_avantages' => 30, 'telecommunication' => 40,
            'monnaie' => 'USD', 'statut' => 'Payée', 'date_paiement' => '2026-09-14', 'mode_paiement' => 'Mobile money',
        ])->assertRedirect()->assertSessionHas('success')->assertSessionHas('warning');

        $this->assertSame(2, RhPaie::where(['annee' => 2026, 'mois' => 9, 'statut' => 'Payée'])->count());
        $this->assertSame(2, RhPaie::whereNotNull('bulletin_genere_le')->count());
        $this->assertSame(2, RhPaie::where('monnaie', 'USD')->whereDate('date_paiement', '2026-09-14')->where('mode_paiement', 'Mobile money')->count());
        $this->assertSame(2, RhPaie::whereNotNull('reference_paiement')->distinct()->count('reference_paiement'));
        $this->assertDatabaseCount('rh_paiements', 2);
        $this->assertSame(1100.0, (float) RhPaie::orderBy('id')->first()->net);
        $this->assertSame(2, RhPaie::where('transport',10)->where('logement',20)->where('autres_avantages',30)->where('telecommunication',40)->count());
        $this->actingAs($u)->post(route('parametres.rh.paie.store-all'), ['annee' => 2026, 'mois' => 9, 'appliquer_retenues' => 1, 'appliquer_retenue_absence' => 1, 'statut' => 'Payée', 'monnaie' => 'USD'])->assertSessionHasErrors(['date_paiement', 'mode_paiement']);
        $this->assertSame(2, RhPaie::where('appliquer_retenues', false)->where('appliquer_retenue_absence', false)->where('retenues', 0)->count());
        $this->actingAs($u)->get(route('parametres.rh.paie'))->assertOk()->assertSee('Générer les bulletins pour tous')->assertSee('Télécharger le PDF collectif')->assertSee('Retenues, taxes et cotisations ?')->assertSee('Retenue sur absence ?');
        $pdf = $this->actingAs($u)->get(route('parametres.rh.paie.download-all', ['annee' => 2026, 'mois' => 9]));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('bulletins-paie-2026-09.pdf', (string) $pdf->headers->get('content-disposition'));
        $this->assertGreaterThanOrEqual(2, substr_count($pdf->getContent(), '/Type /Page'));
    }

    public function test_super_admin_can_edit_and_delete_an_unpaid_payroll(): void
    {
        $u = $this->user();
        $ent = Entreprise::create(['user_id' => $u->id, 'nom_entreprise' => 'ARTICO']);
        $e = Employe::create(['entreprise_id' => $ent->id, 'matricule' => 'EDIT-001', 'nom' => 'Test', 'statut' => 'Actif']);
        $p = RhPaie::create(['entreprise_id' => $ent->id, 'employe_id' => $e->id, 'annee' => 2026, 'mois' => 8, 'salaire_base' => 1000, 'monnaie' => 'USD', 'statut' => 'Validée']);
        $this->actingAs($u)->get(route('parametres.rh.paie'))->assertOk()->assertSee('Modifier')->assertSee('Supprimer');
        $this->get(route('parametres.rh.paie.edit', $p))->assertOk();
        $this->put(route('parametres.rh.paie.update', $p), [
            'salaire_base' => 1200, 'primes' => 100, 'montant_heures_supplementaires' => 0,
            'retenues' => 50, 'total_taxes' => 0, 'total_cotisations' => 0, 'monnaie' => 'USD',
            'transport' => 10, 'logement' => 20, 'autres_avantages' => 30, 'telecommunication' => 40,
            'appliquer_retenue_absence' => 1, 'motif_modification' => 'Correction du salaire',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1350.0, $p->fresh()->net);
        $this->assertSame('À vérifier', $p->fresh()->statut);
        $this->delete(route('parametres.rh.destroy', ['paie', $p->id]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSoftDeleted('rh_paies', ['id' => $p->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'suppression_rh', 'model_id' => $p->id]);
    }

    public function test_payroll_deletion_respects_role_company_and_paid_status(): void
    {
        $u = $this->user();
        $ent = Entreprise::create(['user_id' => $u->id, 'nom_entreprise' => 'ARTICO']);
        $p = RhPaie::create(['entreprise_id' => $ent->id, 'annee' => 2026, 'mois' => 8, 'salaire_base' => 1000, 'monnaie' => 'USD', 'statut' => 'Payée']);
        $url = route('parametres.rh.destroy', ['paie', $p->id]);
        $this->actingAs($this->user('Admin'))->delete($url)->assertSessionHasErrors('suppression');
        $p->update(['statut' => 'Clôturée']);
        $this->delete($url)->assertSessionHasErrors('suppression');
        $p->update(['statut' => 'Calculée']);
        $admin = $this->user('Admin');
        $this->actingAs($admin)->delete($url)->assertForbidden();
        $other = Entreprise::create(['user_id' => $admin->id, 'nom_entreprise' => 'AUTRE']);
        $p->update(['entreprise_id' => $other->id]);
        $this->actingAs($u)->delete($url)->assertNotFound();
        $this->assertNotSoftDeleted('rh_paies', ['id' => $p->id]);
    }
    public function test_super_admin_can_delete_paid_closed_and_payment_linked_payrolls(): void
    {
        $u = $this->user();
        $ent = Entreprise::create(['user_id' => $u->id, 'nom_entreprise' => 'ARTICO']);
        $e = Employe::create(['entreprise_id' => $ent->id, 'matricule' => 'DEL-001', 'nom' => 'Test', 'statut' => 'Actif']);
        $this->actingAs($u);

        foreach (['Payée', 'Clôturée', 'Brouillon'] as $index => $statut) {
            $p = RhPaie::create(['entreprise_id' => $ent->id, 'employe_id' => $e->id, 'annee' => 2026, 'mois' => $index + 1, 'salaire_base' => 1000, 'monnaie' => 'USD', 'statut' => $statut]);
            $paiement = $p->paiements()->create([
                'entreprise_id' => $ent->id, 'employe_id' => $e->id,
                'montant_net_a_payer' => 1000, 'montant_paye' => 1000,
                'devise' => 'USD', 'mode_paiement' => 'Cash', 'date_paiement' => '2026-09-14',
            ]);
            $url = route('parametres.rh.destroy', ['paie', $p->id]);
            $this->get(route('parametres.rh.paie'))->assertOk()->assertSee($url, false)->assertSee('Supprimer');
            $this->delete($url)->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSoftDeleted('rh_paies', ['id' => $p->id]);
            $this->assertDatabaseHas('rh_paiements', ['id' => $paiement->id, 'paie_id' => $p->id]);
            $this->assertDatabaseHas('audit_logs', ['action' => 'suppression_rh', 'model_id' => $p->id]);
        }
    }

    public function test_deleted_payroll_can_be_recreated_without_allowing_active_duplicates(): void
    {
        $u = $this->user();
        $ent = Entreprise::create(['user_id' => $u->id, 'nom_entreprise' => 'ARTICO']);
        $e = Employe::create(['entreprise_id' => $ent->id, 'user_id' => $u->id, 'matricule' => 'RECREATE-001', 'nom' => 'Test', 'statut' => 'Actif']);
        $data = ['entreprise_id' => $ent->id, 'employe_id' => $e->id, 'user_id' => $u->id, 'annee' => 2026, 'mois' => 8, 'salaire_base' => 1000, 'monnaie' => 'USD', 'statut' => 'Validée'];
        for ($i = 0; $i < 2; $i++) {
            $p = RhPaie::create($data);
            $this->actingAs($u)->delete(route('parametres.rh.destroy', ['paie', $p->id]))->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSoftDeleted('rh_paies', ['id' => $p->id]);
        }
        $replacement = RhPaie::create($data);
        $this->assertSame(1, RhPaie::where('employe_id', $e->id)->count());
        $this->assertSame(3, RhPaie::withTrashed()->where('employe_id', $e->id)->count());
        $this->assertFalse($replacement->trashed());
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        RhPaie::create($data);
    }

    public function test_individual_payroll_saves_and_displays_payment_information(): void
    {
        $u = $this->user();
        $ent = Entreprise::create(['user_id' => $u->id, 'nom_entreprise' => 'ARTICO']);
        $e = Employe::create(['entreprise_id' => $ent->id, 'matricule' => 'PAY-001', 'nom' => 'Test', 'statut' => 'Actif']);
        $periode = RhPeriodePaie::create(['entreprise_id' => $ent->id, 'libelle' => 'Septembre', 'date_debut' => '2026-09-01', 'date_fin' => '2026-09-30', 'annee' => 2026, 'mois' => 9, 'statut' => 'Ouverte']);
        RhContrat::create(['entreprise_id' => $ent->id, 'employe_id' => $e->id, 'numero' => 'PAY-CTR-001', 'type' => 'CDI', 'date_debut' => '2026-01-01', 'salaire_base' => 1000, 'devise' => 'USD', 'statut' => 'Actif']);
        RhSyntheseMensuelle::create(['entreprise_id' => $ent->id, 'employe_id' => $e->id, 'periode_paie_id' => $periode->id, 'annee' => 2026, 'mois' => 9, 'statut' => 'Validée']);
        $data = ['employe_id' => $e->id, 'annee' => 2026, 'mois' => 9, 'date_paiement' => '2026-09-14', 'mode_paiement' => 'Mobile money'];
        $this->actingAs($u)->post(route('parametres.rh.paie.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $p = RhPaie::where('employe_id', $e->id)->firstOrFail();
        $this->assertSame('2026-09-14', $p->date_paiement->toDateString());
        $this->assertSame('Mobile money', $p->mode_paiement);
        $this->assertSame('PAY-202609-000001', $p->reference_paiement);
        $this->get(route('parametres.rh.paie'))->assertOk()->assertSee('14/09/2026')->assertSee('Mobile money')->assertSee($p->reference_paiement);
        $this->post(route('parametres.rh.paie.store'), array_merge($data, ['mois' => 10, 'date_paiement' => 'invalide']))->assertSessionHasErrors('date_paiement');
    }

    public function test_bulletin_and_excel_include_company_ids_and_disabled_deductions_keep_labels(): void
    {
        $u = $this->user();
        $this->actingAs($u);
        $ent = Entreprise::create(['user_id'=>$u->id,'nom_entreprise'=>'ARTICO','rccm'=>'CD-TEST-123','id_nat'=>'NAT-456']);
        $e = Employe::create(['entreprise_id'=>$ent->id,'user_id'=>$u->id,'matricule'=>'BEN-1','nom'=>'Mwamba','postnom'=>'Test','prenom'=>'Aline','statut'=>'Actif']);
        $p = RhPaie::create(['entreprise_id'=>$ent->id,'employe_id'=>$e->id,'annee'=>2026,'mois'=>9,'salaire_base'=>1000,'transport'=>10,'logement'=>20,'autres_avantages'=>30,'telecommunication'=>40,'retenues'=>15,'total_taxes'=>25,'appliquer_retenues'=>false,'appliquer_retenue_absence'=>false,'monnaie'=>'USD','statut'=>'Brouillon']);
        $this->assertSame(1100.0, $p->fresh()->net);
        $p->lignes()->create(['code'=>'IPR','libelle'=>'IPR non appliqué','type'=>'Taxe','montant'=>25]);
        $html = view('ressources_humaines.paie.bulletin',['paie'=>$p->fresh(),'entreprise'=>$ent,'gerant'=>null])->render();
        foreach (['CD-TEST-123','NAT-456','Mwamba Test Aline','Transport','Logement','Autres avantages','Télécommunication','IPR non appliqué'] as $texte) $this->assertStringContainsString($texte,$html);
        $this->assertMatchesRegularExpression('/IPR non appliqué<\/td><td><\/td><td class="amount">—<\/td>/u',$html);
        $excel = view('exports.table_excel',['entreprise'=>$ent,'headers'=>['Employé'],'rows'=>[['Mwamba']],'titre'=>'Paie','dateDebut'=>'2026-01-01','dateFin'=>'2026-09-15'])->render();
        $this->assertStringContainsString('CD-TEST-123',$excel);
        $this->assertStringContainsString('NAT-456',$excel);
    }

    private function user(string $role = 'Super Admin'): User
    {
        $r = Role::firstOrCreate(['designation' => $role]);
        return User::create(['nom' => 'Test', 'prenom' => $role, 'email' => uniqid().'@test.local', 'password' => bcrypt('password'), 'role_id' => $r->id, 'statut' => 'Actif', 'password_default' => false]);
    }
}
