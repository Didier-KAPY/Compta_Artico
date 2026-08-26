<?php

namespace Tests\Feature;

use App\Models\EntreeCaisse;
use App\Models\EntreeCaisseLigne;
use App\Models\Entreprise;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\ParametrageComptable;
use App\Models\Role;
use App\Models\User;
use App\Services\WorkflowComptableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntreeCaisseWaitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_cash_entry_reference_starts_with_bec(): void
    {
        [$user] = $this->contexte('Caissier', 0);

        $this->actingAs($user)->post(route('entree-caisses.store'), [
            'date' => now()->toDateString(),
            'monnaie' => 'CDF',
            'designation' => ['Recette'],
            'quantite' => [1],
            'prix_unitaire' => [150],
        ])->assertRedirect()->assertSessionHas('success');

        $entree = EntreeCaisse::where('motif', 'Recette')->latest('id')->firstOrFail();
        $this->assertStringStartsWith('BEC-'.now()->format('ym'), $entree->numero);
    }

    public function test_selected_entry_type_is_used_as_document_prefix(): void
    {
        [$user] = $this->contexte('Caissier', 40);

        foreach (['BEM', 'BEB'] as $index => $typeBon) {
            $this->actingAs($user)->post(route('entree-caisses.store'), [
                'date' => now()->toDateString(),
                'type_bon' => $typeBon,
                'appliquer_tva' => 0,
                'motif' => 'Recette '.$typeBon,
                'monnaie' => 'CDF',
                'designation' => ['Recette'],
                'quantite' => [1],
                'prix_unitaire' => [150 + $index],
            ])->assertRedirect()->assertSessionHas('success');

            $entree = EntreeCaisse::where('type_bon', $typeBon)->firstOrFail();
            $this->assertStringStartsWith($typeBon.'-'.now()->format('ym'), $entree->numero);
            $this->assertSame($typeBon, $entree->type_bon);
        }
    }

    public function test_entry_with_tva_stores_tax_breakdown(): void
    {
        [$user] = $this->contexte('Caissier', 41);

        $this->actingAs($user)->post(route('entree-caisses.store'), [
            'date' => now()->toDateString(),
            'type_bon' => 'BEC',
            'appliquer_tva' => 1,
            'monnaie' => 'CDF',
            'designation' => ['Prestation TTC'],
            'quantite' => [1],
            'prix_unitaire' => [116],
        ])->assertRedirect()->assertSessionHas('success');

        $entree = EntreeCaisse::where('motif', 'Prestation TTC')->firstOrFail();
        $this->assertTrue($entree->appliquer_tva);
        $this->assertEquals(100, $entree->montant_ht);
        $this->assertEquals(16, $entree->montant_tva);
        $this->assertEquals(116, $entree->montant);
    }

    public function test_entry_accepts_decimal_values_with_comma(): void
    {
        [$user] = $this->contexte('Caissier', 43);

        $this->actingAs($user)->post(route('entree-caisses.store'), [
            'date' => now()->toDateString(),
            'type_bon' => 'BEC',
            'appliquer_tva' => '1',
            'taux_tva' => '16,5',
            'motif' => 'Montants avec virgule',
            'monnaie' => 'CDF',
            'designation' => ['Prestation'],
            'quantite' => ['1,5'],
            'prix_unitaire' => ['100,50'],
        ])->assertRedirect()->assertSessionHas('success');

        $entree = EntreeCaisse::where('motif', 'Prestation')->firstOrFail();
        $this->assertEquals(150.75, $entree->montant);
        $this->assertEquals(16, $entree->taux_tva);
        $this->assertEquals(129.96, $entree->montant_ht);
        $this->assertEquals(20.79, $entree->montant_tva);
        $this->assertDatabaseHas('entree_caisse_lignes', [
            'entree_caisse_id' => $entree->id,
            'quantite' => 1.5,
            'prix_unitaire' => 100.5,
            'montant' => 150.75,
        ]);
    }

    public function test_create_form_displays_type_and_tva_fields(): void
    {
        [$user] = $this->contexte('Caissier', 42);

        $this->actingAs($user)->get(route('entree-caisses.create'))
            ->assertOk()
            ->assertSee('Type de bon')
            ->assertSee('BEM')
            ->assertSee('BEB')
            ->assertSee('Traitement TVA')
            ->assertSee('Avec TVA')
            ->assertDontSee('name="motif"', false)
            ->assertDontSee('name="taux_tva"', false)
            ->assertDontSee('Taux TVA (%)');
    }

    public function test_cash_entry_can_be_printed_and_downloaded_with_universal_header(): void
    {
        [$user, $entree] = $this->contexte('Admin', 2);
        Entreprise::forceCreate([
            'user_id' => $user->id,
            'nom_entreprise' => 'Entreprise universelle',
            'slogan' => 'Notre slogan configuré',
            'adresse' => 'Kinshasa',
            'telephone' => '0990000000',
        ]);

        $this->actingAs($user)->get(route('entree-caisses.index'))
            ->assertOk()
            ->assertSee(route('entree-caisses.imprimer', $entree), false)
            ->assertSee(route('entree-caisses.pdf', $entree), false);

        $this->actingAs($user)->get(route('entree-caisses.imprimer', $entree))
            ->assertOk()
            ->assertSee('Entreprise universelle')
            ->assertSee('Notre slogan configuré')
            ->assertSee('Bon d’entrée')
            ->assertSee($entree->numero)
            ->assertDontSee('SYSCOHADA');

        $this->actingAs($user)->get(route('entree-caisses.pdf', $entree))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_validating_cash_entry_redirects_to_prefilled_cash_journal(): void
    {
        [$user, $entree] = $this->contexte('Comptable', 1);
        $entree->update([
            'type_bon' => 'BEC',
            'appliquer_tva' => true,
            'taux_tva' => 16,
            'montant_ht' => 86.21,
            'montant_tva' => 13.79,
        ]);

        $this->actingAs($user)->from(route('entree-caisses.show', $entree))->post(route('entree-caisses.valider', $entree), [
            'observation' => 'Entrée contrôlée',
        ])->assertRedirect(route('entree-caisses.show', $entree))
            ->assertSessionHas('success');

        $this->get(route('journaux.create.caisse'))
            ->assertOk()
            ->assertSee('Journal Caisse')
            ->assertSee($entree->numero)
            ->assertSee('100,00')
            ->assertSee('86,21')
            ->assertSee('13,79')
            ->assertSee('16,00 %');

        $this->assertSame('Validé', $entree->fresh()->statut);
        $this->assertDatabaseHas('journaux', [
            'entree_caisse_id' => $entree->id,
            'reference' => $entree->numero,
            'description' => 'Recette',
            'statut' => 'En attente',
            'entrees_cdf' => 86.21,
            'sorties_cdf' => 0,
            'montant_ttc' => 86.21,
            'montant_tva' => 0,
        ]);
        $this->assertDatabaseHas('journaux', [
            'entree_caisse_id' => $entree->id,
            'reference' => $entree->numero,
            'description' => 'TVA',
            'date' => $entree->date->format('Y-m-d 00:00:00'),
            'monnaie' => 'CDF',
            'entrees_cdf' => 13.79,
            'sorties_cdf' => 0,
            'entrees_usd' => 0,
            'sorties_usd' => 0,
            'montant_tva' => 13.79,
            'statut' => 'En attente',
        ]);
        $this->assertSame(2, Journaux::where('entree_caisse_id', $entree->id)->count());
    }

    public function test_tva_from_usd_entry_is_displayed_in_usd_entries(): void
    {
        [$user, $entree] = $this->contexte('Comptable', 45);
        $entree->update([
            'monnaie' => 'USD',
            'type_bon' => 'BEC',
            'appliquer_tva' => true,
            'montant_ht' => 86.21,
            'montant_tva' => 13.79,
        ]);

        JournalType::where('nature', 'caisse')->update(['monnaie' => 'USD']);

        $this->actingAs($user)->from(route('entree-caisses.show', $entree))->post(route('entree-caisses.valider', $entree), [
            'observation' => 'Entrée USD contrôlée',
        ])->assertRedirect(route('entree-caisses.show', $entree));

        $this->assertDatabaseHas('journaux', [
            'entree_caisse_id' => $entree->id,
            'type' => 'recette',
            'monnaie' => 'USD',
            'entrees_cdf' => 0,
            'sorties_cdf' => 0,
            'entrees_usd' => 86.21,
            'sorties_usd' => 0,
            'montant_ttc' => 86.21,
        ]);
        $this->assertDatabaseHas('journaux', [
            'entree_caisse_id' => $entree->id,
            'description' => 'TVA',
            'monnaie' => 'USD',
            'entrees_cdf' => 0,
            'sorties_cdf' => 0,
            'entrees_usd' => 13.79,
            'sorties_usd' => 0,
        ]);
    }

    public function test_validating_tva_line_also_validates_main_journal_and_its_tva(): void
    {
        [$user, $entree] = $this->contexte('Comptable', 44);
        $entree->update([
            'type_bon' => 'BEC',
            'appliquer_tva' => true,
            'taux_tva' => 16,
            'montant_ht' => 86.21,
            'montant_tva' => 13.79,
        ]);

        $this->actingAs($user)->from(route('entree-caisses.show', $entree))->post(route('entree-caisses.valider', $entree), [
            'observation' => 'Entrée contrôlée',
        ])->assertRedirect(route('entree-caisses.show', $entree));

        $principal = Journaux::where('entree_caisse_id', $entree->id)->where('type', 'recette')->firstOrFail();
        $tva = Journaux::where('entree_caisse_id', $entree->id)->where('description', 'TVA')->firstOrFail();
        $type = JournalType::findOrFail($principal->journal_type_id);
        $operation = ListeDesComptes::create([
            'user_id' => $user->id,
            'compte' => '701044',
            'designation' => 'Produits des prestations',
            'nature' => 'Produit',
        ]);

        app(WorkflowComptableService::class)->validerJournalAvecTva($tva, $type->id, $operation->id);

        $this->assertSame('Validé', $principal->fresh()->statut);
        $this->assertSame('Validé', $tva->fresh()->statut);
        $this->assertSame($user->id, $tva->fresh()->valide_par);
        $this->assertNotNull($tva->fresh()->date_validation);
        $this->assertDatabaseCount('ecritures_comptables', 1);
        $ecritureTresorerie = $principal->ecritures()->firstOrFail();
        $this->assertSame($type->liste_des_comptes_id, $ecritureTresorerie->liste_des_comptes_id);
        $this->assertEquals(100.0, (float) $ecritureTresorerie->debit_cdf);
        $this->assertEquals(0.0, (float) $ecritureTresorerie->credit_cdf);
        $this->assertSame('En attente', $ecritureTresorerie->statut);
        $this->assertDatabaseMissing('ecritures_comptables', ['journal_id' => $tva->id]);

        $this->actingAs($user)->get(route('ecritures.show', $ecritureTresorerie))
            ->assertOk()
            ->assertSee('Montant TVA inclus')
            ->assertSee('13,79 CDF')
            ->assertSee('Contrepartie à imputer')
            ->assertSee('86,21 CDF')
            ->assertDontSee('Montant de la contrepartie')
            ->assertSee('name="imputations[0][montant]"', false)
            ->assertSee('placeholder="Montant"', false)
            ->assertSee('data-max="20"', false)
            ->assertSee('Ajouter une ligne')
            ->assertSee('name="piece_justificative"', false)
            ->assertDontSee('name="imputations[0][piece_justificative]"', false)
            ->assertSee('Cette pièce unique sera liée à toutes les lignes')
            ->assertSee('data-expected="100.00"', false)
            ->assertSee('id="validerImputation"', false)
            ->assertSee('disabled', false)
            ->assertSee('Écriture équilibrée : vous pouvez valider.')
            ->assertSee("field.removeAttribute('aria-hidden')", false)
            ->assertSee("option.removeAttribute('data-select2-id')", false)
            ->assertSee("window.jQuery.fn.select2", false);


        $this->actingAs($user)->post(route('ecritures.valider', $ecritureTresorerie), [
            'imputations' => [[
                'liste_des_comptes_id' => $operation->id,
                'montant' => 80,
            ]],
        ])->assertSessionHasErrors('imputations');
        $this->assertDatabaseCount('ecritures_comptables', 1);

        $this->actingAs($user)->post(route('ecritures.valider', $ecritureTresorerie), [
            'imputations' => [
                ['liste_des_comptes_id' => $operation->id, 'montant' => 86.21],
                ['liste_des_comptes_id' => $tva->liste_des_comptes_id, 'montant' => 13.79],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseCount('ecritures_comptables', 3);
        $contreparties = $principal->ecritures()
            ->whereIn('liste_des_comptes_id', [$operation->id, $tva->liste_des_comptes_id])
            ->get();
        $this->assertCount(2, $contreparties);
        $this->assertEqualsWithDelta(100, (float) $contreparties->sum('credit_cdf'), 0.001);
        $this->assertTrue($contreparties->every(
            fn ($ligne) => $ligne->piece === $principal->reference
                && $ligne->statut === 'Validé'
        ));
        $this->assertDatabaseHas('ecritures_comptables', [
            'journal_id' => $principal->id,
            'liste_des_comptes_id' => $tva->liste_des_comptes_id,
            'piece' => $principal->reference,
            'debit_cdf' => 0,
            'credit_cdf' => 13.79,
            'statut' => 'Validé',
        ]);
        $this->assertDatabaseMissing('ecritures_comptables', ['journal_id' => $tva->id]);
    }

    public function test_validating_entry_redirects_according_to_document_type(): void
    {
        [$user, $entree] = $this->contexte('Comptable', 2);
        $compteId = JournalType::query()->value('liste_des_comptes_id');
        JournalType::create(['user_id' => $user->id, 'code' => 'MOB-TEST', 'libelle' => 'Mobile', 'liste_des_comptes_id' => $compteId, 'nature' => 'mobile_money', 'monnaie' => 'CDF', 'est_tresorerie' => true]);
        JournalType::create(['user_id' => $user->id, 'code' => 'BQ-TEST', 'libelle' => 'Banque', 'liste_des_comptes_id' => $compteId, 'nature' => 'banque', 'monnaie' => 'CDF', 'est_tresorerie' => true]);

        foreach (['BEM' => 'journaux.create.mobile', 'BEB' => 'journaux.create.banque'] as $typeBon => $route) {
            $bon = $entree->replicate()->fill([
                'numero' => $typeBon.'-TEST',
                'type_bon' => $typeBon,
                'statut' => 'En attente',
                'date_validation' => null,
                'valide_par' => null,
            ]);
            $bon->save();
            $bon->lignes()->create([
                'designation' => 'Recette', 'quantite' => 1, 'prix_unitaire' => 100, 'montant' => 100,
            ]);

            $this->actingAs($user)->from(route('entree-caisses.show', $bon))->post(route('entree-caisses.valider', $bon), [
                'observation' => 'Entrée contrôlée',
            ])->assertRedirect(route('entree-caisses.show', $bon));

            $this->assertDatabaseHas('journaux', ['entree_caisse_id' => $bon->id, 'statut' => 'En attente']);
        }
    }

    public function test_super_admin_can_return_entry_to_waiting_before_journal_validation(): void
    {
        [$user, $entree, $journal] = $this->contexte('Super Admin', 10, 'En attente');

        $this->actingAs($user)->patch(route('entree-caisses.reouvrir', $entree))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('En attente', $entree->fresh()->statut);
        $this->assertSoftDeleted($journal);
    }

    public function test_validated_journal_blocks_returning_entry_to_waiting(): void
    {
        [$user, $entree, $journal] = $this->contexte('Gérant', 20, 'Validé');

        $this->actingAs($user)->patch(route('entree-caisses.reouvrir', $entree))
            ->assertForbidden();

        $this->assertSame('Validé', $entree->fresh()->statut);
        $this->assertSame('Validé', $journal->fresh()->statut);
    }

    public function test_legacy_journal_linked_by_reference_also_blocks_return_to_waiting(): void
    {
        [$user, $entree, $journal] = $this->contexte('Super Admin', 30, 'Validé');
        $journal->update(['entree_caisse_id' => null]);

        $this->actingAs($user)->patch(route('entree-caisses.reouvrir', $entree))
            ->assertSessionHasErrors('statut');

        $this->assertSame('Validé', $entree->fresh()->statut);
    }

    public function test_pending_legacy_journal_linked_by_reference_is_deleted(): void
    {
        [$user, $entree, $journal] = $this->contexte('Super Admin', 31, 'En attente');
        $journal->update(['entree_caisse_id' => null]);

        $this->actingAs($user)->patch(route('entree-caisses.reouvrir', $entree))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('En attente', $entree->fresh()->statut);
        $this->assertSoftDeleted($journal);
    }

    private function contexte(string $designation, int $index, ?string $statutJournal = null): array
    {
        $role = Role::create(['designation' => $designation]);
        $user = User::create([
            'nom' => 'Test', 'prenom' => 'Entrée', 'email' => 'entree'.$index.'@test.local',
            'password' => bcrypt('password'), 'role_id' => $role->id,
            'password_default' => false, 'statut' => 'Actif',
        ]);
        $compte = ListeDesComptes::create([
            'user_id' => $user->id, 'compte' => '571'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'designation' => 'Caisse', 'nature' => 'Actif',
        ]);
        $type = JournalType::create([
            'user_id' => $user->id, 'code' => 'EC'.$index, 'libelle' => 'Caisse',
            'liste_des_comptes_id' => $compte->id, 'nature' => 'caisse',
            'monnaie' => 'CDF', 'est_tresorerie' => true,
        ]);
        $compteTva = ListeDesComptes::create([
            'user_id' => $user->id, 'compte' => '443'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'designation' => 'TVA facturée', 'nature' => 'Passif',
        ]);
        ParametrageComptable::create([
            'user_id' => $user->id,
            'code' => 'TVA_FACTUREE',
            'designation' => 'TVA facturée',
            'liste_des_comptes_id' => $compteTva->id,
            'actif' => true,
        ]);
        $entree = EntreeCaisse::create([
            'user_id' => $user->id, 'numero' => 'BEC-W-'.$index, 'date' => now(),
            'motif' => 'Recette', 'montant' => 100, 'monnaie' => 'CDF',
            'statut' => $statutJournal === null ? 'En attente' : 'Validé',
        ]);
        EntreeCaisseLigne::create([
            'entree_caisse_id' => $entree->id, 'designation' => 'Recette',
            'quantite' => 1, 'prix_unitaire' => 100, 'montant' => 100,
        ]);

        $journal = null;
        if ($statutJournal !== null) {
            $journal = Journaux::create([
                'user_id' => $user->id, 'journal_type_id' => $type->id,
                'liste_des_comptes_id' => $compte->id, 'entree_caisse_id' => $entree->id,
                'reference' => $entree->numero, 'date' => now(),
                'description' => 'Entrée', 'statut' => $statutJournal,
            ]);
        }

        return [$user, $entree, $journal];
    }
}

