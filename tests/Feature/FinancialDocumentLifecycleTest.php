<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Departement;
use App\Models\EtatBesoin;
use App\Models\Role;
use App\Models\SortieCaisse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialDocumentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletion_requires_reason_minimum_length_and_strong_confirmation(): void
    {
        [$user, $etat] = $this->context('Super Admin', 'EB-VALIDATION');

        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), [
            'strategie' => 'individuelle', 'confirmation_comptable' => '1',
        ])->assertSessionHasErrors('motif');

        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), [
            'motif' => 'Trop bref', 'strategie' => 'individuelle', 'confirmation_comptable' => '1',
        ])->assertSessionHasErrors('motif');

        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), [
            'motif' => 'Motif comptable suffisamment détaillé.', 'strategie' => 'individuelle',
        ])->assertSessionHasErrors('confirmation_comptable');

        $this->assertNotSoftDeleted($etat);
    }

    public function test_validated_document_deletion_is_forbidden_to_non_super_admin(): void
    {
        [$user, $etat] = $this->context('Comptable', 'EB-FORBIDDEN', 'Validé');
        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), $this->payload())
            ->assertForbidden();
        $this->assertNotSoftDeleted($etat);
    }

    public function test_individual_deletion_is_refused_when_active_dependency_exists(): void
    {
        [$user, $etat] = $this->context('Super Admin', 'EB-DEPENDENCY');
        $sortie = $this->sortie($user, $etat, 'BSC-DEPENDENCY');

        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), $this->payload())
            ->assertSessionHasErrors('strategie');

        $this->assertNotSoftDeleted($etat);
        $this->assertNotSoftDeleted($sortie);
    }

    public function test_soft_deletion_creates_complete_audit_and_hides_document_from_normal_lists(): void
    {
        [$user, $etat] = $this->context('Super Admin', 'EB-AUDIT', 'Validé');
        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), $this->payload())
            ->assertRedirect(route('etat-besoins.index'));

        $this->assertSoftDeleted($etat);
        $this->assertNull(EtatBesoin::find($etat->id));
        $audit = AuditLog::where('model_type', EtatBesoin::class)->where('model_id', $etat->id)->firstOrFail();
        $this->assertSame('suppression_document_valide', $audit->action);
        $this->assertSame($user->id, $audit->user_id);
        $this->assertSame('Validé', $audit->ancien_statut);
        $this->assertSame('EB-AUDIT', $audit->ancienne_valeur['numero']);
        $this->assertNotEmpty($audit->adresse_ip);
    }

    public function test_deleted_document_is_visible_in_trash_and_can_be_restored_with_audit(): void
    {
        [$user, $etat] = $this->context('Super Admin', 'EB-RESTORE');
        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), $this->payload());

        $this->actingAs($user)->get(route('corbeille.index'))
            ->assertOk()->assertSee('EB-RESTORE');
        $this->actingAs($user)->post(route('corbeille.restore', ['etats-besoin', $etat->id]), ['cascade' => false])
            ->assertRedirect(route('corbeille.index'));

        $this->assertNotSoftDeleted($etat);
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => EtatBesoin::class, 'model_id' => $etat->id, 'action' => 'restauration',
        ]);
        $this->assertNotNull($etat->fresh()->restaure_le);
    }

    public function test_controlled_cascade_soft_deletes_every_document_and_audits_each_one(): void
    {
        [$user, $etat] = $this->context('Super Admin', 'EB-CASCADE', 'Validé');
        $sortie = $this->sortie($user, $etat, 'BSC-CASCADE', 'Validé');

        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), $this->payload('cascade'))
            ->assertRedirect(route('etat-besoins.index'));

        $this->assertSoftDeleted($etat);
        $this->assertSoftDeleted($sortie);
        $this->assertSame(2, AuditLog::where('action', 'suppression_cascade')->count());
    }

    public function test_force_delete_is_forbidden_to_non_super_admin_and_audited_for_super_admin(): void
    {
        [$super, $etat] = $this->context('Super Admin', 'EB-FORCE');
        $this->actingAs($super)->delete(route('etat-besoins.destroy', $etat), $this->payload());

        $role = Role::create(['designation' => 'Comptable']);
        $ordinary = $this->user($role, 'ordinary-force@test.local');
        $forcePayload = ['motif' => 'Suppression définitive exceptionnelle justifiée.', 'confirmation_comptable' => '1', 'phrase_confirmation' => 'SUPPRIMER DÉFINITIVEMENT'];
        $this->actingAs($ordinary)->delete(route('corbeille.force-delete', ['etats-besoin', $etat->id]), $forcePayload)->assertForbidden();

        $this->actingAs($super)->delete(route('corbeille.force-delete', ['etats-besoin', $etat->id]), $forcePayload)
            ->assertRedirect(route('corbeille.index'));
        $this->assertDatabaseMissing('etat_besoins', ['id' => $etat->id]);
        $this->assertDatabaseHas('audit_logs', ['model_type' => EtatBesoin::class, 'model_id' => $etat->id, 'action' => 'suppression_definitive']);
    }

    public function test_super_admin_can_monitor_sensitive_actions_from_settings_dashboard(): void
    {
        [$user, $etat] = $this->context('Super Admin', 'EB-MONITORING', 'Validé');
        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), $this->payload());

        $this->actingAs($user)->get(route('parametres.parametre'))
            ->assertOk()->assertSee('Suivi et alertes comptables');
        $this->actingAs($user)->get(route('parametres.audit.index'))
            ->assertOk()
            ->assertSee('Signalement de sécurité')
            ->assertSee('EB-MONITORING')
            ->assertSee('suppression document valide');
    }

    public function test_super_admin_can_empty_the_financial_audit_log(): void
    {
        [$user, $etat] = $this->context('Super Admin', 'EB-EMPTY-AUDIT');
        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), $this->payload());
        $this->assertGreaterThan(0, AuditLog::count());

        $this->actingAs($user)->delete(route('parametres.audit.empty'), [
            'confirmation'=>'1',
            'phrase_confirmation'=>"vider le journal d'audit",
        ])->assertRedirect(route('parametres.audit.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSoftDeleted($etat);
    }

    public function test_super_admin_can_empty_the_trash_with_strong_confirmation_and_audit(): void
    {
        [$user, $etat] = $this->context('Super Admin', 'EB-EMPTY');
        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), $this->payload());

        $this->actingAs($user)->get(route('corbeille.index'))
            ->assertOk()->assertSee('Vider la corbeille');

        $this->actingAs($user)->delete(route('corbeille.empty'), [
            'motif' => 'Nettoyage définitif contrôlé de la corbeille.',
            'confirmation_comptable' => '1',
            'phrase_confirmation' => 'VIDER LA CORBEILLE',
        ])->assertRedirect(route('corbeille.index'));

        $this->assertDatabaseMissing('etat_besoins', ['id' => $etat->id]);
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => EtatBesoin::class,
            'model_id' => $etat->id,
            'action' => 'suppression_definitive',
            'type_suppression' => 'vidage de la corbeille',
        ]);
    }

    public function test_need_lines_amount_and_currency_can_be_updated_without_budget(): void
    {
        [$user, $etat] = $this->context('Super Admin', 'EB-EDIT-AMOUNT');

        $this->actingAs($user)->get(route('etat-besoins.edit', $etat))
            ->assertOk()
            ->assertSee('Désignation')
            ->assertSee('Quantité')
            ->assertSee('Prix unitaire')
            ->assertSee('Monnaie')
            ->assertDontSee('Rubrique budgétaire');

        $this->actingAs($user)->put(route('etat-besoins.update', $etat), [
            'departement_id' => $etat->departement_id,
            'demandeur' => 'Demandeur modifié',
            'monnaie' => 'USD',
            'ligne_budgetaire_id' => null,
            'designation' => ['Fourniture de test'],
            'quantite' => [2],
            'prix_unitaire' => [137.75],
        ])->assertRedirect(route('etat-besoins.show', $etat));

        $etat->refresh();
        $this->assertEquals(275.50, $etat->montant_estime);
        $this->assertSame('Demandeur modifié', $etat->demandeur);
        $this->assertSame('USD', $etat->monnaie);
        $this->assertDatabaseHas('etat_besoin_lignes', [
            'etat_besoin_id' => $etat->id,
            'designation' => 'Fourniture de test',
            'quantite' => 2,
            'prix_unitaire' => 137.75,
            'montant' => 275.50,
        ]);
    }

    private function context(string $roleName, string $numero, string $statut = 'En attente'): array
    {
        $role = Role::create(['designation' => $roleName]);
        $user = $this->user($role, strtolower(str_replace(' ', '-', $numero)).'@test.local');
        $departement = Departement::create(['designation' => 'Département '.$numero]);
        $etat = EtatBesoin::create(['user_id' => $user->id, 'departement_id' => $departement->id, 'numero' => $numero,
            'date' => now(), 'service' => $departement->designation, 'demandeur' => 'Demandeur', 'motif' => 'Besoin test',
            'montant_estime' => 100, 'monnaie' => 'CDF', 'statut' => $statut]);
        return [$user, $etat];
    }

    private function user(Role $role, string $email): User
    {
        return User::create(['nom' => 'Test', 'prenom' => 'Audit', 'email' => $email, 'password' => bcrypt('password'),
            'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif']);
    }

    private function sortie(User $user, EtatBesoin $etat, string $numero, string $statut = 'En attente'): SortieCaisse
    {
        return SortieCaisse::create(['user_id' => $user->id, 'etat_besoin_id' => $etat->id, 'numero' => $numero,
            'date' => now(), 'beneficiaire' => 'Demandeur', 'motif' => 'Test', 'montant' => 100,
            'monnaie' => 'CDF', 'statut' => $statut]);
    }

    private function payload(string $strategie = 'individuelle'): array
    {
        return ['motif' => 'Correction comptable documentée et approuvée.', 'strategie' => $strategie, 'confirmation_comptable' => '1'];
    }
}
