<?php

namespace Tests\Feature;

use App\Models\CarteService;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\Fonction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarteServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_cinq_roles_autorises_accedent_au_module(): void
    {
        foreach (['Super Admin', 'Admin', 'Gérant', 'Directeur Général', 'Directeur Technique', 'Chargé Technique'] as $index => $designation) {
            $user = $this->userWithRole($designation, $index);

            $this->actingAs($user)->get(route('parametres.parametre'))
                ->assertOk()->assertSee('Cartes de service');
            $this->actingAs($user)->get(route('parametres.cartes-service.index'))->assertOk();
            $this->actingAs($user)->get(route('parametres.cartes-service.create'))->assertOk();
        }
    }

    public function test_un_role_non_autorise_ne_peut_pas_acceder_aux_cartes(): void
    {
        $user = $this->userWithRole('Comptable', 20);

        $this->actingAs($user)->get(route('parametres.parametre'))
            ->assertOk()->assertDontSee('Cartes de service');
        $this->actingAs($user)->get(route('parametres.cartes-service.index'))->assertForbidden();
    }

    public function test_creation_et_affichage_utilisent_les_donnees_de_l_agent(): void
    {
        $admin = $this->userWithRole('Super Admin', 30);
        $departement = Departement::create(['designation' => 'Direction technique']);
        $fonction = Fonction::create(['designation' => 'Ingénieur principal']);
        $agent = $this->userWithRole('Agent', 31, [
            'nom' => 'KAPY', 'prenom' => 'Didier', 'adresse' => '12, avenue du Fleuve',
            'departement_id' => $departement->id, 'fonction_id' => $fonction->id,
        ]);
        $admin->entreprises()->create([
            'nom_entreprise' => 'ARTICO SARL',
            'adresse' => 'Kinshasa', 'telephone' => '0990000000',
        ]);

        $response = $this->actingAs($admin)->post(route('parametres.cartes-service.store'), [
            'user_id' => $agent->id,
            'postnom' => 'MUKENDI',
            'adresse' => '',
            'date_naissance' => '1990-06-15',
            'sexe' => 'Masculin',
            'date_delivrance' => '2026-07-30',
            'nom_signataire' => 'Jean Gérant',
        ]);

        $carte = CarteService::firstOrFail();
        $response->assertRedirect(route('parametres.cartes-service.show', $carte));
        $this->assertSame('CS-2026-00001', $carte->numero);
        $this->assertDatabaseHas('cartes_service', [
            'user_id' => $agent->id, 'postnom' => 'MUKENDI', 'nom_signataire' => 'Jean Gérant',
        ]);

        $this->actingAs($admin)->get(route('parametres.cartes-service.show', $carte))
            ->assertOk()
            ->assertSee('ARTICO SARL')
            ->assertSee('KAPY MUKENDI Didier')
            ->assertSee('Direction technique')
            ->assertSee('Ingénieur principal')
            ->assertSee('12, avenue du Fleuve')
            ->assertSee('Jean Gérant');

        $this->actingAs($admin)->get(route('parametres.cartes-service.pdf', $carte))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    private function userWithRole(string $designation, int $index, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['designation' => $designation]);

        return User::create(array_merge([
            'nom' => 'Test', 'prenom' => $designation,
            'email' => 'carte'.$index.'@test.local', 'password' => bcrypt('password'),
            'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif',
        ], $attributes));
    }
}
