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