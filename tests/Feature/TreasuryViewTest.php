<?php

namespace Tests\Feature;

use App\Models\Journaux;
use App\Models\JournalType;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreasuryViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_treasury_and_statement_show_real_balances_and_opening_balance(): void
    {
        $role = Role::create(['designation' => 'Admin']);
        $user = User::create([
            'nom' => 'Test', 'prenom' => 'Admin', 'email' => 'treasury@test.local',
            'password' => bcrypt('password'), 'role_id' => $role->id,
            'password_default' => 0, 'statut' => 'Actif',
        ]);
        $account = ListeDesComptes::create([
            'user_id' => $user->id, 'compte' => '571100', 'designation' => 'Caisse principale', 'nature' => 'Actif',
        ]);
        $type = JournalType::create([
            'user_id' => $user->id, 'code' => 'CAI', 'libelle' => 'Caisse',
            'liste_des_comptes_id' => $account->id, 'nature' => 'caisse', 'est_tresorerie' => true,
        ]);

        $bankAccount = ListeDesComptes::create([
            'user_id' => $user->id, 'compte' => '521100', 'designation' => 'Banque', 'nature' => 'Actif',
        ]);
        $bankType = JournalType::create([
            'user_id' => $user->id, 'code' => 'BQ', 'libelle' => 'Banque',
            'liste_des_comptes_id' => $bankAccount->id, 'nature' => 'banque', 'est_tresorerie' => true,
        ]);

        $mobileAccount = ListeDesComptes::create([
            'user_id' => $user->id, 'compte' => '532100', 'designation' => 'Mobile Money', 'nature' => 'Actif',
        ]);
        $mobileType = JournalType::create([
            'user_id' => $user->id, 'code' => 'MOB', 'libelle' => 'Mobile Money',
            'liste_des_comptes_id' => $mobileAccount->id, 'nature' => 'mobile_money', 'est_tresorerie' => true,
        ]);

        $this->journal($user, $type, '2026-06-30', 'OUV', 100, 0);
        $this->journal($user, $type, '2026-07-02', 'ENT', 50, 0);
        $this->journal($user, $type, '2026-07-03', 'SOR', 0, 20);
        $this->journal($user, $bankType, '2026-07-04', 'BQ-ENT', 200, 0, 25, 0);
        $this->journal($user, $mobileType, '2026-07-05', 'MOB-ENT', 75, 0, 10, 0);

        $query = ['date_debut' => '2026-07-01', 'date_fin' => '2026-07-31', 'journal_type_id' => $type->id];

        $this->actingAs($user)->get(route('journaux.tresorerie', $query))
            ->assertOk()
            ->assertSee('Situation de trésorerie')
            ->assertSee('Solde en caisse CDF')->assertSee('130,00')
            ->assertSee('Solde en caisse USD')
            ->assertSee('Solde en banque CDF')->assertSee('200,00')
            ->assertSee('Solde en banque USD')->assertSee('25,00')
            ->assertSee('Solde Mobile Money CDF')->assertSee('75,00')
            ->assertSee('Solde Mobile Money USD')->assertSee('10,00');

        $this->actingAs($user)->get(route('journaux.releve', $query))
            ->assertOk()->assertSee('SOLDE D’OUVERTURE')->assertSee('100,00')->assertSee('130,00');
    }

    public function test_statement_displays_the_tva_account_instead_of_the_treasury_account(): void
    {
        $role = Role::create(['designation' => 'Admin']);
        $user = User::create([
            'nom' => 'Test', 'prenom' => 'TVA', 'email' => 'statement-tva@test.local',
            'password' => bcrypt('password'), 'role_id' => $role->id,
            'password_default' => 0, 'statut' => 'Actif',
        ]);
        $treasuryAccount = ListeDesComptes::create([
            'user_id' => $user->id, 'compte' => '571100',
            'designation' => 'Caisse principale', 'nature' => 'Actif',
        ]);
        $tvaAccount = ListeDesComptes::create([
            'user_id' => $user->id, 'compte' => '443100',
            'designation' => 'TVA facturée', 'nature' => 'Passif',
        ]);
        $type = JournalType::create([
            'user_id' => $user->id, 'code' => 'CAI', 'libelle' => 'Caisse',
            'liste_des_comptes_id' => $treasuryAccount->id, 'nature' => 'caisse',
            'monnaie' => 'CDF', 'est_tresorerie' => true,
        ]);
        Journaux::create([
            'user_id' => $user->id, 'journal_type_id' => $type->id,
            'liste_des_comptes_id' => $tvaAccount->id,
            'reference' => 'BEC-TVA-001', 'date' => '2026-07-02',
            'description' => 'TVA', 'statut' => 'Validé',
            'entrees_cdf' => 16, 'sorties_cdf' => 0,
        ]);

        $this->actingAs($user)->get(route('journaux.releve', [
            'date_debut' => '2026-07-01',
            'date_fin' => '2026-07-31',
            'journal_type_id' => $type->id,
        ]))
            ->assertOk()
            ->assertSee('BEC-TVA-001')
            ->assertSee('<td>443100</td>', false)
            ->assertDontSee('<td>571100</td>', false);
    }
    public function test_statement_uses_the_correct_tva_label_for_entries_and_outputs(): void
    {
        $entryTva = new Journaux(['description' => 'TVA']);
        $entryTva->forceFill(['entree_caisse_id' => 10]);

        $outputTva = new Journaux(['description' => 'TVA']);
        $outputTva->forceFill(['sortie_caisse_id' => 20]);

        $this->assertSame('TVA_FACTUREE', $entryTva->libelle_releve);
        $this->assertSame('TVA_RECUPERABLE', $outputTva->libelle_releve);
    }
    private function journal(
        User $user,
        JournalType $type,
        string $date,
        string $reference,
        float $in,
        float $out,
        float $inUsd = 0,
        float $outUsd = 0
    ): void
    {
        Journaux::create([
            'user_id' => $user->id, 'journal_type_id' => $type->id, 'reference' => $reference,
            'date' => $date, 'description' => $reference, 'statut' => 'Validé',
            'entrees_cdf' => $in, 'sorties_cdf' => $out,
            'entrees_usd' => $inUsd, 'sorties_usd' => $outUsd,
        ]);
    }
}
