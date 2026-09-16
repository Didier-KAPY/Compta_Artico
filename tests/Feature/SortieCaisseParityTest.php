<?php

namespace Tests\Feature;

use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\ParametrageComptable;
use App\Models\Role;
use App\Models\SortieCaisse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SortieCaisseParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_output_with_tva_uses_detailed_lines_and_creates_two_usd_journal_lines(): void
    {
        $user = $this->user();
        $this->journal($user, 'mobile_money', 'USD', '532100');
        $compteTva = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '445110', 'designation' => 'TVA récupérable', 'nature' => 'Actif']);
        ParametrageComptable::create(['user_id' => $user->id, 'code' => 'TVA_RECUPERABLE', 'designation' => 'TVA récupérable', 'liste_des_comptes_id' => $compteTva->id]);

        $this->actingAs($user)->post(route('sortie-caisses.store'), [
            'date' => now()->toDateString(),
            'type_bon' => 'BSM',
            'beneficiaire' => 'Fournisseur mobile',
            'monnaie' => 'USD',
            'appliquer_tva' => 1,
            'observation' => 'Paiement mobile',
            'designation' => ['Service TTC'],
            'quantite' => ['1,00'],
            'prix_unitaire' => ['116,00'],
        ])->assertRedirect(route('sortie-caisses.create'))->assertSessionHas('success');

        $sortie = SortieCaisse::latest('id')->firstOrFail();
        $this->assertSame('BSM', $sortie->type_bon);
        $this->assertSame('Mobile Money', $sortie->type);
        $this->assertSame('Service TTC', $sortie->motif);
        $this->assertStringStartsWith('BSM-', $sortie->numero);
        $this->assertEquals(100, $sortie->montant_ht);
        $this->assertEquals(16, $sortie->montant_tva);
        $this->assertDatabaseHas('sortie_caisse_lignes', [
            'sortie_caisse_id' => $sortie->id,
            'designation' => 'Service TTC (HT)',
            'montant' => 100,
        ]);
        $this->assertDatabaseHas('sortie_caisse_lignes', [
            'sortie_caisse_id' => $sortie->id,
            'designation' => 'TVA 16 %',
            'montant' => 16,
        ]);
        $this->assertSame(2, $sortie->lignesCloture()->count());
        $this->actingAs($user)->get(route('sortie-caisses.index'))
            ->assertOk()
            ->assertSee('Service TTC (HT)')
            ->assertSee('TVA 16,00 %');

        $this->actingAs($user)->from(route('sortie-caisses.show', $sortie))->post(route('sortie-caisses.valider', $sortie))
            ->assertRedirect(route('sortie-caisses.show', $sortie))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('journaux', [
            'sortie_caisse_id' => $sortie->id,
            'reference' => $sortie->numero,
            'description' => 'Service TTC',
            'sorties_usd' => 100,
            'sorties_cdf' => 0,
        ]);
        $this->assertDatabaseHas('journaux', [
            'sortie_caisse_id' => $sortie->id,
            'reference' => $sortie->numero,
            'description' => 'TVA',
            'sorties_usd' => 16,
            'sorties_cdf' => 0,
        ]);
        $this->assertSame(2, Journaux::where('sortie_caisse_id', $sortie->id)->count());

        $journaux = Journaux::where('sortie_caisse_id', $sortie->id)->orderBy('id')->get();
        foreach ($journaux as $ligneJournal) {
            $this->actingAs($user)->get(route('journaux.recu', $ligneJournal))
                ->assertOk()
                ->assertSee($sortie->numero)
                ->assertSee('100,00')
                ->assertSee('TVA (16,00 %)')
                ->assertSee('16,00')
                ->assertSee('116,00');
        }
    }

    public function test_bank_output_without_tva_creates_one_cdf_journal_line(): void
    {
        $user = $this->user();
        $this->journal($user, 'banque', 'CDF', '521100');

        $this->actingAs($user)->post(route('sortie-caisses.store'), [
            'date' => now()->toDateString(),
            'type_bon' => 'BSB',
            'beneficiaire' => 'Fournisseur banque',
            'monnaie' => 'CDF',
            'appliquer_tva' => 0,
            'designation' => ['Achat sans TVA'],
            'quantite' => [2],
            'prix_unitaire' => [50],
        ])->assertRedirect(route('sortie-caisses.create'));

        $sortie = SortieCaisse::latest('id')->firstOrFail();
        $this->assertStringStartsWith('BSB-', $sortie->numero);

        $this->actingAs($user)->from(route('sortie-caisses.show', $sortie))->post(route('sortie-caisses.valider', $sortie))
            ->assertRedirect(route('sortie-caisses.show', $sortie));

        $this->assertDatabaseHas('journaux', [
            'sortie_caisse_id' => $sortie->id,
            'sorties_cdf' => 100,
            'montant_ht' => 100,
            'montant_tva' => 0,
        ]);
        $this->assertSame(1, Journaux::where('sortie_caisse_id', $sortie->id)->count());
    }

    public function test_pending_output_without_number_gets_reference_from_selected_nature_on_validation(): void
    {
        $user = $this->user();
        $this->journal($user, 'banque', 'CDF', '521200');
        $sortie = SortieCaisse::create([
            'user_id' => $user->id, 'numero' => null, 'type_bon' => null,
            'date' => now()->toDateString(), 'beneficiaire' => 'Bénéficiaire test',
            'motif' => 'Besoin validé', 'montant' => 1000, 'montant_ht' => 1000,
            'montant_tva' => 0, 'monnaie' => 'CDF', 'statut' => 'En attente',
            'type' => 'Caisse',
        ]);

        $this->actingAs($user)->from(route('sortie-caisses.show', $sortie))
            ->post(route('sortie-caisses.valider', $sortie), ['type_bon' => 'BSB'])
            ->assertRedirect(route('sortie-caisses.show', $sortie));

        $sortie->refresh();
        $this->assertSame('BSB', $sortie->type_bon);
        $this->assertSame('Banque', $sortie->type);
        $this->assertStringStartsWith('BSB-', $sortie->numero);
    }

    public function test_charge_des_finances_peut_consulter_et_valider_une_sortie_en_attente(): void
    {
        $role = Role::firstOrCreate(['designation' => 'Chargé des finances']);
        $user = User::create([
            'nom' => 'Finance',
            'prenom' => 'Validation',
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'password_default' => false,
            'statut' => 'Actif',
        ]);
        $this->journal($user, 'caisse', 'CDF', '571100');
        $sortie = SortieCaisse::create([
            'user_id' => $user->id,
            'numero' => 'BSC-TEST-FINANCE',
            'type_bon' => 'BSC',
            'date' => now()->toDateString(),
            'beneficiaire' => 'Bénéficiaire test',
            'motif' => 'Validation financière',
            'montant' => 500,
            'montant_ht' => 500,
            'montant_tva' => 0,
            'monnaie' => 'CDF',
            'statut' => 'En attente',
            'type' => 'Caisse',
        ]);

        $this->actingAs($user)->get(route('sortie-caisses.index'))
            ->assertOk()
            ->assertSee('Actions')
            ->assertSee('Valider')
            ->assertSee('ouvrir_validation=1', false)
            ->assertSee(route('sortie-caisses.show', $sortie), false);
        $this->actingAs($user)->get(route('sortie-caisses.show', $sortie))
            ->assertOk()
            ->assertSee('Traiter le bon')
            ->assertSee('id="modalTraitement"', false)
            ->assertDontSee('Consultation uniquement');
        $this->actingAs($user)->get(route('sortie-caisses.edit', $sortie))->assertForbidden();

        $this->actingAs($user)->from(route('sortie-caisses.show', $sortie))
            ->post(route('sortie-caisses.valider', $sortie), ['type_bon' => 'BSC'])
            ->assertRedirect(route('sortie-caisses.show', $sortie))
            ->assertSessionHas('success');

        $sortie->refresh();
        $this->assertSame('Validé', $sortie->statut);
        $this->assertEquals($user->id, $sortie->valide_par);
    }

    public function test_conversion_uses_saved_rate_in_both_directions_and_preserves_decimals(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $this->journal($user, 'caisse', 'CDF', '571100');
        $this->journal($user, 'caisse', 'USD', '571200');
        foreach (['CDF' => ['USD', '0.123456789'], 'USD' => ['CDF', '123456789000']] as $source => [$target, $expected]) {
            $etat = \App\Models\EtatBesoin::create([
                'user_id' => $user->id, 'numero' => 'EB-CONV-'.$source, 'date' => today(), 'service' => 'Test',
                'demandeur' => 'Test', 'motif' => 'Conversion', 'montant_estime' => 123456.789, 'monnaie' => $source, 'statut' => 'Validé',
            ]);
            $sortie = SortieCaisse::create([
                'user_id' => $user->id, 'etat_besoin_id' => $etat->id, 'date' => today(),
                'beneficiaire' => 'Test', 'motif' => 'Conversion', 'montant' => 123456.789, 'monnaie' => $source, 'statut' => 'En attente',
            ]);
            $data = ['type_bon' => 'BSC', 'convertir_traitement' => 1];
            if ($source === 'CDF') {
                $this->post(route('sortie-caisses.valider', $sortie), $data)->assertSessionHasErrors('conversion');
                $this->assertDatabaseCount('journaux', 0);
                \App\Models\TauxDeChange::create(['user_id' => $user->id, 'taux_de_change' => 1000000, 'devise_source' => 'USD', 'devise_cible' => 'CDF', 'date_taux' => today()]);
            }
            $this->get(route('sortie-caisses.show', $sortie))->assertOk()
                ->assertDontSee('name="somme_disponible"', false)
                ->assertDontSee('name="monnaie_traitement"', false)
                ->assertSee('id="montant-traitement-converti"', false)
                ->assertSee(str_replace('.', ',', $expected));
            // The saved amount and currency remain authoritative even with obsolete form fields.
            $data['somme_disponible'] = '1';
            $data['monnaie_traitement'] = $target;
            $this->post(route('sortie-caisses.valider', $sortie), $data)->assertSessionHas('success');
            $this->assertSame($target, $sortie->fresh()->monnaie);
            $this->assertEqualsWithDelta((float) $expected, (float) $sortie->fresh()->montant, 0.000000000001);
            $journal = Journaux::where('sortie_caisse_id', $sortie->id)->firstOrFail();
            $this->assertEqualsWithDelta((float) $expected, (float) $journal->montant_ttc, 0.000000000001);
            $this->assertEquals(1000000, $sortie->fresh()->taux_conversion);
        }
    }

    public function test_accounting_recovers_exact_need_total_despite_recurring_conversion_and_rate_change(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $this->journal($user, 'caisse', 'USD', '571200');
        $compteTva = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '445110', 'designation' => 'TVA récupérable', 'nature' => 'Actif']);
        ParametrageComptable::create(['user_id' => $user->id, 'code' => 'TVA_RECUPERABLE', 'designation' => 'TVA', 'liste_des_comptes_id' => $compteTva->id]);
        foreach ([false, true] as $avecTva) {
            \App\Models\TauxDeChange::create(['user_id' => $user->id, 'taux_de_change' => 2800, 'devise_source' => 'USD', 'devise_cible' => 'CDF', 'date_taux' => today()]);
            $etat = \App\Models\EtatBesoin::create([
                'user_id' => $user->id, 'numero' => 'EB-EXACT-'.(int) $avecTva, 'date' => today(), 'service' => 'Test',
                'demandeur' => 'Test', 'motif' => 'Conversion exacte', 'montant_estime' => 10000, 'monnaie' => 'CDF', 'statut' => 'Validé',
            ]);
            $sortie = SortieCaisse::create([
                'user_id' => $user->id, 'etat_besoin_id' => $etat->id, 'date' => today(), 'beneficiaire' => 'Test',
                'motif' => 'Conversion exacte', 'montant' => 10000, 'monnaie' => 'CDF', 'statut' => 'En attente',
                'appliquer_tva' => $avecTva, 'taux_tva' => $avecTva ? 16 : 0,
            ]);
            $this->post(route('sortie-caisses.valider', $sortie), ['type_bon' => 'BSC', 'convertir_traitement' => 1])->assertSessionHas('success');
            \App\Models\TauxDeChange::create(['user_id' => $user->id, 'taux_de_change' => 3500, 'devise_source' => 'USD', 'devise_cible' => 'CDF', 'date_taux' => today()]);
            $journal = Journaux::where('sortie_caisse_id', $sortie->id)->where('type', 'depense')->firstOrFail();
            app(\App\Services\WorkflowComptableService::class)->validerJournalAvecTva($journal);
            $this->assertEquals('10000.00', $journal->ecritures()->firstOrFail()->credit_cdf);
            $this->assertEquals(10000, $etat->fresh()->montant_estime);
        }
    }

    private function user(): User
    {
        $role = Role::create(['designation' => 'Super Admin']);

        return User::create([
            'nom' => 'Sortie',
            'prenom' => 'Test',
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'password_default' => false,
            'statut' => 'Actif',
        ]);
    }

    private function journal(User $user, string $nature, string $monnaie, string $numero): JournalType
    {
        $compte = ListeDesComptes::create([
            'user_id' => $user->id,
            'compte' => $numero,
            'designation' => ucfirst(str_replace('_', ' ', $nature)),
            'nature' => 'Actif',
        ]);

        return JournalType::create([
            'user_id' => $user->id,
            'code' => strtoupper(substr($nature, 0, 3)).$monnaie,
            'libelle' => ucfirst(str_replace('_', ' ', $nature)),
            'liste_des_comptes_id' => $compte->id,
            'nature' => $nature,
            'monnaie' => $monnaie,
            'est_tresorerie' => true,
        ]);
    }
}
