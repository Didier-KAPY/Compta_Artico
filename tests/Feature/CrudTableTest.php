<?php

namespace Tests\Feature;

use App\Models\Journaux;
use App\Models\JournalType;
use App\Models\Role;
use App\Models\SortieCaisse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudTableTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::create(['designation' => 'Admin']);
        $this->user = User::create([
            'nom' => 'CRUD', 'prenom' => 'Admin', 'email' => 'crud@test.local',
            'password' => bcrypt('password'), 'role_id' => $role->id,
            'password_default' => 0, 'statut' => 'Actif',
        ]);
        $this->actingAs($this->user);
    }

    public function test_sortie_caisse_crud_pages_and_actions_work(): void
    {
        $this->get(route('sortie-caisses.create'))->assertOk();

        $sortie = SortieCaisse::create([
            'user_id' => $this->user->id, 'numero' => 'BS-TEST', 'date' => now()->toDateString(),
            'beneficiaire' => 'Ancien', 'motif' => 'Test', 'montant' => 100,
            'monnaie' => 'CDF', 'statut' => 'En attente', 'type' => 'Caisse', 'observation' => 'Test',
        ]);

        $this->get(route('sortie-caisses.edit', $sortie))->assertOk();
        $this->put(route('sortie-caisses.update', $sortie), [
            'date' => now()->toDateString(), 'beneficiaire' => 'Nouveau', 'motif' => 'Modification',
            'montant' => 250, 'monnaie' => 'CDF', 'type' => 'Banque', 'observation' => 'Modifié',
        ])->assertRedirect(route('sortie-caisses.show', $sortie));
        $this->assertDatabaseHas('sortie_caisses', ['id' => $sortie->id, 'beneficiaire' => 'Nouveau', 'type' => 'Banque']);

        $this->delete(route('sortie-caisses.destroy', $sortie))->assertRedirect(route('sortie-caisses.index'));
        $this->assertDatabaseMissing('sortie_caisses', ['id' => $sortie->id]);
    }

    public function test_pending_journal_metadata_can_be_updated_and_deleted(): void
    {
        $journalType = JournalType::create([
            'user_id' => $this->user->id, 'code' => 'CAI', 'libelle' => 'Caisse',
            'nature' => 'caisse', 'est_tresorerie' => true,
        ]);
        $journal = Journaux::create([
            'user_id' => $this->user->id, 'journal_type_id' => $journalType->id,
            'reference' => 'JR-TEST', 'date' => now()->toDateString(),
            'description' => 'Avant', 'statut' => 'En attente',
        ]);

        $this->get(route('journaux.edit', $journal))->assertOk();
        $this->put(route('journaux.update', $journal), [
            'date' => now()->toDateString(), 'nom_partenaire' => 'Partenaire',
            'telephone_partenaire' => '000', 'adresse_partenaire' => 'Adresse',
            'description' => 'Après',
        ])->assertRedirect(route('journaux.show', $journal));
        $this->assertDatabaseHas('journaux', ['id' => $journal->id, 'description' => 'Après']);

        $this->delete(route('journaux.destroy', $journal))->assertRedirect(route('journaux.index'));
        $this->assertDatabaseMissing('journaux', ['id' => $journal->id]);
    }
}
