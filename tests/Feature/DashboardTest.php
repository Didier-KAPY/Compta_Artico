<?php

namespace Tests\Feature;

use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\User;
use App\Services\DashboardService;
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
            ->assertSee('10 dernières opérations')
            ->assertSee('BRC')
            ->assertSee("Bons d'entrée")
            ->assertSee('Nouveau BRC')
            ->assertDontSee('Validé par')
            ->assertSee('window.location.reload(), 30000', false);
    }

    public function test_director_has_the_same_dashboard_as_admin(): void
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
            ->assertSee('Situation de caisse')
            ->assertSee('Entrées vs sorties par mois')
            ->assertSee('10 dernières opérations')
            ->assertDontSee('Validé par');
    }

    public function test_manager_has_the_same_dashboard_as_admin(): void
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
            ->assertSee('Disponibilités par compte')
            ->assertSee('Situation de caisse')
            ->assertSee('Entrées vs sorties par mois')
            ->assertSee('10 dernières opérations')
            ->assertDontSee('Validé par');
    }

    public function test_cash_situation_only_includes_validated_treasury_movements_up_to_today(): void
    {
        $role = Role::create(['designation' => 'Admin']);
        $user = User::create([
            'nom' => 'Test',
            'prenom' => 'Tresorerie',
            'email' => 'tresorerie-dashboard@test.local',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'password_default' => 0,
            'statut' => 'Actif',
        ]);
        $compte = ListeDesComptes::create([
            'user_id' => $user->id,
            'compte' => '571100',
            'designation' => 'Caisse principale',
            'nature' => 'Actif',
        ]);
        $tresorerie = JournalType::create([
            'user_id' => $user->id,
            'code' => 'CAI',
            'libelle' => 'Journal caisse',
            'liste_des_comptes_id' => $compte->id,
            'nature' => 'caisse',
            'monnaie' => 'CDF',
            'est_tresorerie' => true,
        ]);
        $brc = JournalType::create([
            'user_id' => $user->id,
            'code' => 'OD',
            'libelle' => 'Opérations diverses',
            'liste_des_comptes_id' => $compte->id,
            'nature' => 'od',
            'monnaie' => 'CDF',
            'est_tresorerie' => false,
        ]);

        foreach ([
            [$tresorerie, 'Validé', now()->toDateString(), 100],
            [$brc, 'Validé', now()->toDateString(), 900],
            [$tresorerie, 'En attente', now()->toDateString(), 800],
            [$tresorerie, 'Validé', now()->addDay()->toDateString(), 700],
        ] as $index => [$journalType, $statut, $date, $montant]) {
            Journaux::create([
                'user_id' => $user->id,
                'journal_type_id' => $journalType->id,
                'liste_des_comptes_id' => $compte->id,
                'reference' => 'TEST-'.($index + 1),
                'date' => $date,
                'type' => $journalType->nature === 'od' ? 'od' : 'recette',
                'monnaie' => 'CDF',
                'entrees_cdf' => $montant,
                'statut' => $statut,
            ]);
        }

        $cash = app(DashboardService::class)->getData($user)['cash'];

        $this->assertSame(100.0, $cash['in_cdf']);
        $this->assertSame(100.0, $cash['balance_cdf']);
    }
}
