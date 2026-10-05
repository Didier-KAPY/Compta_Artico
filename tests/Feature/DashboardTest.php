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
            ->assertDontSee('Situation de caisse')
            ->assertSee('Entrées vs sorties par jour')
            ->assertDontSee('10 dernières opérations')
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
            ->assertDontSee('Situation de caisse')
            ->assertSee('Entrées vs sorties par jour')
            ->assertDontSee('10 dernières opérations')
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
            ->assertSee('Mouvements nets par compte')
            ->assertDontSee('Situation de caisse')
            ->assertSee('Entrées vs sorties par jour')
            ->assertDontSee('10 dernières opérations')
            ->assertDontSee('Validé par');
    }

    public function test_charge_finances_voit_toutes_les_sections_du_tableau_de_bord(): void
    {
        foreach (['Chargé des finances', 'Chargé de finance', 'Charge de finance', 'Charger de finance'] as $index => $designation) {
            $role = Role::firstOrCreate(['designation' => $designation]);
            $user = User::create([
                'nom' => 'Test', 'prenom' => 'Finances', 'email' => 'finances'.$index.'@test.local',
                'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => 0, 'statut' => 'Actif',
            ]);
            $this->actingAs($user)->get(route('dashboard'))->assertOk()
                ->assertSee('Situation de trésorerie')->assertSee('Mouvements nets par compte')
                ->assertDontSee('Situation de caisse')->assertSee('Entrées vs sorties par jour')
                ->assertDontSee('10 dernières opérations')
                ->assertViewHas('sections', fn ($sections) => collect($sections)->except('needs_only')->every(fn ($visible) => $visible === true));
        }
    }

    public function test_cash_situation_only_includes_validated_treasury_movements_up_to_today(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-05 12:00:00'));
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
            [$tresorerie, 'Validé', now()->startOfMonth()->subDay()->toDateString(), 600],
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

    public function test_dashboard_defaults_to_current_month_and_can_display_another_year(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-05 12:00:00'));
        $role = Role::create(['designation' => 'Admin']);
        $user = User::create(['nom' => 'Test', 'prenom' => 'Mois', 'email' => 'month@test.local',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => 0, 'statut' => 'Actif']);
        $account = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '571100', 'designation' => 'Caisse', 'nature' => 'Actif']);
        $type = JournalType::create(['user_id' => $user->id, 'code' => 'CAI', 'libelle' => 'Caisse',
            'liste_des_comptes_id' => $account->id, 'nature' => 'caisse', 'monnaie' => 'CDF', 'est_tresorerie' => true]);
        foreach (['2026-10-01' => 100, '2026-09-30' => 900, '2025-02-28' => 200, '2025-03-01' => 800] as $date => $amount) {
            Journaux::create(['user_id' => $user->id, 'journal_type_id' => $type->id, 'liste_des_comptes_id' => $account->id,
                'reference' => $date, 'date' => $date, 'type' => 'recette', 'monnaie' => 'CDF', 'mode_paiement' => 'banque',
                'entrees_cdf' => $amount, 'montant_ttc' => $amount, 'statut' => 'Validé']);
            Journaux::create(['user_id' => $user->id, 'journal_type_id' => $type->id, 'liste_des_comptes_id' => $account->id,
                'reference' => 'WAIT-'.$date, 'date' => $date, 'type' => 'depense', 'monnaie' => 'CDF', 'statut' => 'En attente']);
            \App\Models\EtatBesoin::create(['user_id' => $user->id, 'numero' => 'EB-'.$date, 'date' => $date,
                'service' => 'Test', 'demandeur' => 'Test', 'motif' => 'Test', 'monnaie' => 'CDF', 'statut' => 'Validé']);
        }

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Mois à consulter')->assertViewHas('selectedMonth', '2026-10')
            ->assertViewHas('cash', fn ($cash) => $cash['in_cdf'] === 100.0)
            ->assertViewHas('charts', fn ($charts) => count($charts['labels']) === 31 && array_sum($charts['in_cdf']) === 100.0);
        $this->get(route('dashboard', ['mois' => '2025-02']))->assertOk()
            ->assertViewHas('selectedMonth', '2025-02')
            ->assertViewHas('statistics', fn ($stats) => $stats['needs'] === 1)
            ->assertViewHas('cash', fn ($cash) => $cash['in_cdf'] === 200.0)
            ->assertViewHas('treasury_situation', fn ($data) => $data['totals']['total_cdf'] === 200.0)
            ->assertViewHas('validations', fn ($data) => $data['journals'] === 1)
            ->assertViewHas('accounting_alerts', fn ($data) => $data['etats_besoin_sans_piece'] === 1)
            ->assertViewHas('latest_operations', fn ($rows) => $rows->count() === 2 && $rows->every(fn ($row) => $row->date->format('Y-m') === '2025-02'))
            ->assertViewHas('charts', fn ($charts) => count($charts['labels']) === 28 && $charts['in_cdf'][27] === 200.0
                && array_sum($charts['in_cdf']) === 200.0 && $charts['operations'][0] === 200.0 && $charts['payments'][1] === 1);
        $this->get(route('dashboard', ['mois' => '2025-13']))->assertSessionHasErrors('mois');
        $this->get(route('dashboard', ['mois' => '2025-01']))->assertOk()
            ->assertViewHas('cash', fn ($cash) => $cash['in_cdf'] === 0.0)
            ->assertViewHas('charts', fn ($charts) => array_sum($charts['in_cdf']) === 0.0);
    }
}
