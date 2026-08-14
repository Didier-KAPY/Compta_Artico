<?php

namespace Tests\Feature;

use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompteJournalTypeCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_account_also_creates_an_operations_diverses_journal_type(): void
    {
        $role = Role::create(['designation' => 'DAF']);
        $user = User::create([
            'nom' => 'Test', 'prenom' => 'DAF', 'email' => 'daf-compte@test.local',
            'password' => bcrypt('password'), 'role_id' => $role->id,
            'password_default' => false, 'statut' => 'Actif',
        ]);

        $this->actingAs($user)->post(route('parametres.comptes.store'), [
            'compte' => '571100',
            'designation' => 'Caisse principale',
            'nature' => 'Actif',
        ])->assertRedirect(route('parametres.comptes'));

        $compte = ListeDesComptes::where('compte', '571100')->firstOrFail();

        $this->assertDatabaseHas('journal_types', [
            'user_id' => $user->id,
            'code' => 'CAI',
            'libelle' => 'Caisse principale',
            'liste_des_comptes_id' => $compte->id,
            'nature' => 'od',
            'monnaie' => 'CDF',
            'est_tresorerie' => false,
        ]);
    }

    public function test_un_numero_de_compte_ne_peut_pas_etre_cree_deux_fois(): void
    {
        $role = Role::create(['designation' => 'DAF']);
        $user = User::create([
            'nom' => 'Test', 'prenom' => 'DAF', 'email' => 'daf-unique@test.local',
            'password' => bcrypt('password'), 'role_id' => $role->id,
            'password_default' => false, 'statut' => 'Actif',
        ]);
        ListeDesComptes::create([
            'user_id' => $user->id,
            'compte' => '571100',
            'designation' => 'Caisse existante',
            'nature' => 'Actif',
        ]);

        $this->actingAs($user)->from(route('parametres.comptes.create'))->post(route('parametres.comptes.store'), [
            'compte' => ' 571100 ',
            'designation' => 'Compte dupliqué',
            'nature' => 'Actif',
        ])->assertRedirect(route('parametres.comptes.create'))
            ->assertSessionHasErrors('compte');

        $this->assertDatabaseCount('liste_des_comptes', 1);
    }
}
