<?php

namespace Tests\Feature;

use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\SortieCaisse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SortieCaisseWaitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_comptable_ne_voit_pas_la_colonne_actions(): void
    {
        [$user] = $this->contexte('Comptable', 99, 'En attente');

        $this->actingAs($user)->get(route('sortie-caisses.index'))
            ->assertOk()
            ->assertDontSee('Actions');
    }

    public function test_management_can_set_output_to_waiting_before_cash_validation(): void
    {
        foreach (['Super Admin', 'Admin', 'Directeur Général', 'Gérant', 'Gerant'] as $index => $designation) {
            [$user, $sortie, $journal] = $this->contexte($designation, $index, 'En attente');

            $this->actingAs($user)->post(route('sortie-caisses.attente', $sortie))
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertSame('En attente', $sortie->fresh()->statut);
            $this->assertSoftDeleted($journal);
        }
    }

    public function test_cash_validated_output_cannot_be_set_to_waiting(): void
    {
        [$user, $sortie, $journal] = $this->contexte('Gérant', 20, 'Validé');

        $this->actingAs($user)->post(route('sortie-caisses.attente', $sortie))
            ->assertRedirect()
            ->assertSessionHasErrors('statut');

        $this->assertSame('Validé', $sortie->fresh()->statut);
        $this->assertModelExists($journal);
    }

    public function test_management_can_reject_before_journal_validation(): void
    {
        [$user, $sortie, $journal] = $this->contexte('Directeur Général', 30, 'En attente');

        $this->actingAs($user)->post(route('sortie-caisses.rejeter', $sortie))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Rejeté', $sortie->fresh()->statut);
        $this->assertSoftDeleted($journal);
    }

    public function test_management_cannot_reject_after_journal_validation(): void
    {
        [$user, $sortie, $journal] = $this->contexte('Admin', 40, 'Validé');

        $this->actingAs($user)->post(route('sortie-caisses.rejeter', $sortie))
            ->assertRedirect()
            ->assertSessionHasErrors('statut');

        $this->assertSame('Validé', $sortie->fresh()->statut);
        $this->assertModelExists($journal);
    }

    public function test_legacy_journal_linked_only_by_reference_also_blocks_actions(): void
    {
        [$user, $sortie, $journal] = $this->contexte('Super Admin', 50, 'Validé');
        $journal->update(['sortie_caisse_id' => null]);

        $this->actingAs($user)->post(route('sortie-caisses.attente', $sortie))
            ->assertSessionHasErrors('statut');
        $this->actingAs($user)->post(route('sortie-caisses.rejeter', $sortie))
            ->assertSessionHasErrors('statut');

        $this->assertSame('Validé', $sortie->fresh()->statut);
        $this->assertModelExists($journal);
    }

    public function test_lowercase_validated_status_also_blocks_actions(): void
    {
        [$user, $sortie, $journal] = $this->contexte('Gérant', 60, 'Validé');
        $journal->forceFill(['statut' => 'validé'])->save();

        $this->actingAs($user)->post(route('sortie-caisses.attente', $sortie))
            ->assertSessionHasErrors('statut');
        $this->actingAs($user)->post(route('sortie-caisses.rejeter', $sortie))
            ->assertSessionHasErrors('statut');

        $this->assertSame('Validé', $sortie->fresh()->statut);
        $this->assertModelExists($journal);
    }

    private function contexte(string $designation, int $index, string $statutJournal): array
    {
        $role = Role::create(['designation' => $designation]);
        $user = User::create(['nom' => 'Test', 'prenom' => 'Gestion', 'email' => 'sortie'.$index.'@test.local',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif']);
        $compte = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '571'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'designation' => 'Caisse', 'nature' => 'Actif']);
        $type = JournalType::create(['user_id' => $user->id, 'code' => 'CAI'.$index, 'libelle' => 'Caisse',
            'liste_des_comptes_id' => $compte->id, 'nature' => 'caisse', 'est_tresorerie' => true]);
        $sortie = SortieCaisse::create(['user_id' => $user->id, 'numero' => 'BSC-W-'.$index, 'date' => now(),
            'beneficiaire' => 'Test', 'motif' => 'Test', 'montant' => 100, 'monnaie' => 'CDF', 'statut' => 'Validé']);
        $journal = Journaux::create(['user_id' => $user->id, 'journal_type_id' => $type->id,
            'liste_des_comptes_id' => $compte->id, 'sortie_caisse_id' => $sortie->id,
            'reference' => $sortie->numero, 'date' => now(), 'description' => 'Sortie', 'statut' => $statutJournal]);

        return [$user, $sortie, $journal];
    }
}
