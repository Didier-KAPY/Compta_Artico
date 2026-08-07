<?php

namespace Tests\Feature;

use App\Models\EntreeCaisse;
use App\Models\EntreeCaisseLigne;
use App\Models\Entreprise;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntreeCaisseWaitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_cash_entry_reference_starts_with_bec(): void
    {
        [$user] = $this->contexte('Caissier', 0);

        $this->actingAs($user)->post(route('entree-caisses.store'), [
            'date' => now()->toDateString(),
            'motif' => 'Nouvelle recette',
            'monnaie' => 'CDF',
            'designation' => ['Recette'],
            'quantite' => [1],
            'prix_unitaire' => [150],
        ])->assertRedirect()->assertSessionHas('success');

        $entree = EntreeCaisse::where('motif', 'Nouvelle recette')->firstOrFail();
        $this->assertStringStartsWith('BEC-'.now()->format('ym'), $entree->numero);
    }

    public function test_cash_entry_can_be_printed_and_downloaded_with_universal_header(): void
    {
        [$user, $entree] = $this->contexte('Admin', 2);
        Entreprise::forceCreate([
            'user_id' => $user->id,
            'nom_entreprise' => 'Entreprise universelle',
            'slogan' => 'Notre slogan configuré',
            'adresse' => 'Kinshasa',
            'telephone' => '0990000000',
        ]);

        $this->actingAs($user)->get(route('entree-caisses.index'))
            ->assertOk()
            ->assertSee(route('entree-caisses.imprimer', $entree), false)
            ->assertSee(route('entree-caisses.pdf', $entree), false);

        $this->actingAs($user)->get(route('entree-caisses.imprimer', $entree))
            ->assertOk()
            ->assertSee('Entreprise universelle')
            ->assertSee('Notre slogan configuré')
            ->assertSee('Bon d’entrée')
            ->assertSee($entree->numero)
            ->assertDontSee('SYSCOHADA');

        $this->actingAs($user)->get(route('entree-caisses.pdf', $entree))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_validating_cash_entry_creates_a_pending_journal(): void
    {
        [$user, $entree] = $this->contexte('Comptable', 1);

        $this->actingAs($user)->post(route('entree-caisses.valider', $entree), [
            'observation' => 'Entrée contrôlée',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Validé', $entree->fresh()->statut);
        $this->assertDatabaseHas('journaux', [
            'entree_caisse_id' => $entree->id,
            'reference' => $entree->numero,
            'statut' => 'En attente',
            'valide_par' => null,
            'date_validation' => null,
        ]);
    }

    public function test_super_admin_can_return_entry_to_waiting_before_journal_validation(): void
    {
        [$user, $entree, $journal] = $this->contexte('Super Admin', 10, 'En attente');

        $this->actingAs($user)->patch(route('entree-caisses.reouvrir', $entree))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('En attente', $entree->fresh()->statut);
        $this->assertSoftDeleted($journal);
    }

    public function test_validated_journal_blocks_returning_entry_to_waiting(): void
    {
        [$user, $entree, $journal] = $this->contexte('Gérant', 20, 'Validé');

        $this->actingAs($user)->patch(route('entree-caisses.reouvrir', $entree))
            ->assertForbidden();

        $this->assertSame('Validé', $entree->fresh()->statut);
        $this->assertSame('Validé', $journal->fresh()->statut);
    }

    public function test_legacy_journal_linked_by_reference_also_blocks_return_to_waiting(): void
    {
        [$user, $entree, $journal] = $this->contexte('Super Admin', 30, 'Validé');
        $journal->update(['entree_caisse_id' => null]);

        $this->actingAs($user)->patch(route('entree-caisses.reouvrir', $entree))
            ->assertSessionHasErrors('statut');

        $this->assertSame('Validé', $entree->fresh()->statut);
    }

    public function test_pending_legacy_journal_linked_by_reference_is_deleted(): void
    {
        [$user, $entree, $journal] = $this->contexte('Super Admin', 31, 'En attente');
        $journal->update(['entree_caisse_id' => null]);

        $this->actingAs($user)->patch(route('entree-caisses.reouvrir', $entree))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('En attente', $entree->fresh()->statut);
        $this->assertSoftDeleted($journal);
    }

    private function contexte(string $designation, int $index, ?string $statutJournal = null): array
    {
        $role = Role::create(['designation' => $designation]);
        $user = User::create([
            'nom' => 'Test', 'prenom' => 'Entrée', 'email' => 'entree'.$index.'@test.local',
            'password' => bcrypt('password'), 'role_id' => $role->id,
            'password_default' => false, 'statut' => 'Actif',
        ]);
        $compte = ListeDesComptes::create([
            'user_id' => $user->id, 'compte' => '571'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'designation' => 'Caisse', 'nature' => 'Actif',
        ]);
        $type = JournalType::create([
            'user_id' => $user->id, 'code' => 'EC'.$index, 'libelle' => 'Caisse',
            'liste_des_comptes_id' => $compte->id, 'nature' => 'caisse', 'est_tresorerie' => true,
        ]);
        $entree = EntreeCaisse::create([
            'user_id' => $user->id, 'numero' => 'BEC-W-'.$index, 'date' => now(),
            'motif' => 'Recette', 'montant' => 100, 'monnaie' => 'CDF',
            'statut' => $statutJournal === null ? 'En attente' : 'Validé',
        ]);
        EntreeCaisseLigne::create([
            'entree_caisse_id' => $entree->id, 'designation' => 'Recette',
            'quantite' => 1, 'prix_unitaire' => 100, 'montant' => 100,
        ]);

        $journal = null;
        if ($statutJournal !== null) {
            $journal = Journaux::create([
                'user_id' => $user->id, 'journal_type_id' => $type->id,
                'liste_des_comptes_id' => $compte->id, 'entree_caisse_id' => $entree->id,
                'reference' => $entree->numero, 'date' => now(),
                'description' => 'Entrée', 'statut' => $statutJournal,
            ]);
        }

        return [$user, $entree, $journal];
    }
}
