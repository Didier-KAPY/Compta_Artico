<?php

namespace Tests\Feature;

use App\Models\{JournalType, Journaux, ListeDesComptes, Role, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreasuryStatementDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_statement_matches_period_totals_and_keeps_opening_balance_separate(): void
    {
        $role = Role::create(['designation' => 'Super Admin']);
        $user = User::create(['nom' => 'Test', 'prenom' => 'Releve', 'email' => 'releve@test.local', 'password' => bcrypt('password'),
            'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif']);
        $this->actingAs($user);
        $account = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '55221', 'designation' => 'Mobile USD', 'nature' => 'Actif']);
        $mobile = JournalType::create(['user_id' => $user->id, 'code' => 'MM USD', 'libelle' => 'Mobile Money',
            'liste_des_comptes_id' => $account->id, 'nature' => 'mobile_money', 'monnaie' => 'USD', 'est_tresorerie' => true]);
        $other = JournalType::create(['user_id' => $user->id, 'code' => 'AUTRE', 'libelle' => 'Autre journal',
            'liste_des_comptes_id' => $account->id, 'nature' => 'banque', 'monnaie' => 'USD', 'est_tresorerie' => true]);
        foreach ([
            [$mobile, '2026-08-31', 100, 0, 'OUVERTURE', 'Validé'],
            [$mobile, '2026-09-01', 571.74, 0, 'ENTREE-MM', 'Validé'],
            [$mobile, '2026-09-30', 0, 1405.94, 'SORTIE-MM', 'Validé'],
            [$mobile, '2026-10-01', 999, 0, 'APRES-PERIODE', 'Validé'],
            [$mobile, '2026-09-15', 999, 0, 'EN-ATTENTE', 'En attente'],
            [$other, '2026-09-15', 999, 0, 'AUTRE-COMPTE', 'Validé'],
        ] as [$type, $date, $in, $out, $reference, $status]) {
            Journaux::create(['user_id' => $user->id, 'journal_type_id' => $type->id, 'liste_des_comptes_id' => $account->id,
                'date' => $date, 'reference' => $reference, 'description' => $reference, 'monnaie' => 'USD',
                'entrees_usd' => $in, 'sorties_usd' => $out, 'statut' => $status]);
        }
        $period = ['date_debut' => '2026-09-01', 'date_fin' => '2026-09-30'];
        $parameters = $period + ['journal_type_id' => $mobile->id];
        $this->get(route('journaux.tresorerie', $period))->assertOk()
            ->assertSee(e(route('journaux.releve', $parameters)), false)
            ->assertViewHas('tresorerie', fn ($rows) => (float) $rows->firstWhere('journal_type_id', $mobile->id)->sortie_usd === 1405.94);
        $this->get(route('journaux.releve', $parameters))->assertOk()
            ->assertSee('Synthèse du compte MM USD')->assertSee('55221')
            ->assertSee('571,74')->assertSee('1 405,94')->assertSee('-834,20')
            ->assertViewHas('ouverture', fn ($row) => (float) $row->usd === 100.0)
            ->assertViewHas('totaux', fn ($totals) => round($totals['solde_usd'], 2) === -734.20)
            ->assertViewHas('journaux', fn ($rows) => $rows->pluck('reference')->all() === ['ENTREE-MM', 'SORTIE-MM']);
        $this->get(route('exports.periode', $parameters + ['rapport' => 'releve', 'format' => 'excel']))->assertOk()
            ->assertSee('SORTIE-MM')->assertSee('VARIATION DE LA PÉRIODE (HORS OUVERTURE)')->assertSee('-834,20');
    }
}
