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
        ])->assertRedirect()->assertSessionHas('success')->assertSessionHas('warning');

        $this->assertSame(2, RhPaie::where(['annee' => 2026, 'mois' => 9, 'statut' => 'Bulletin généré'])->count());
        $this->assertSame(2, RhPaie::whereNotNull('bulletin_genere_le')->count());
        $this->assertSame(2, RhPaie::where('appliquer_retenues', false)->where('appliquer_retenue_absence', false)->where('retenues', 0)->count());
        $this->actingAs($u)->get(route('parametres.rh.paie'))->assertOk()->assertSee('Générer les bulletins pour tous')->assertSee('Télécharger le PDF collectif')->assertSee('Retenues, taxes et cotisations ?')->assertSee('Retenue sur absence ?');
        $pdf = $this->actingAs($u)->get(route('parametres.rh.paie.download-all', ['annee' => 2026, 'mois' => 9]));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('bulletins-paie-2026-09.pdf', (string) $pdf->headers->get('content-disposition'));
        $this->assertGreaterThanOrEqual(2, substr_count($pdf->getContent(), '/Type /Page'));
    }

    private function user(string $role = 'Super Admin'): User
    {
        $r = Role::firstOrCreate(['designation' => $role]);
        return User::create(['nom' => 'Test', 'prenom' => $role, 'email' => uniqid().'@test.local', 'password' => bcrypt('password'), 'role_id' => $r->id, 'statut' => 'Actif', 'password_default' => false]);
    }
}
