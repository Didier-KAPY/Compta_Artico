<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_dashboard_with_real_aggregates(): void
    {
        $role = Role::create(['designation' => 'Admin']);
        $user = User::create([
            'nom' => 'Test',
            'prenom' => 'Admin',
            'email' => 'dashboard@test.local',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'password_default' => 0,
            'statut' => 'Actif',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Situation de caisse')
            ->assertSee('Entrées vs sorties par mois')
            ->assertSee('10 dernières opérations');
    }

    public function test_director_sees_statistics_and_treasury_situation(): void
    {
        $role = Role::create(['designation' => 'Directeur Général']);
        $user = User::create([
            'nom' => 'Test',
            'prenom' => 'Direction',
            'email' => 'direction@test.local',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'password_default' => 0,
            'statut' => 'Actif',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Statistiques')
            ->assertSee('Situation de trésorerie')
            ->assertDontSee('Situation de caisse')
            ->assertDontSee('10 dernières opérations');
    }

    public function test_manager_sees_treasury_situation(): void
    {
        $role = Role::create(['designation' => 'Gérant']);
        $user = User::create([
            'nom' => 'Test',
            'prenom' => 'Gerant',
            'email' => 'gerant@test.local',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'password_default' => 0,
            'statut' => 'Actif',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Situation de trésorerie')
            ->assertSee('Disponibilités par compte');
    }
}
