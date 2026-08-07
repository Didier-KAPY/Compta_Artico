<?php

namespace Tests\Feature;

use App\Models\EcritureComptable;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\ParametrageComptable;
use App\Models\Role;
use App\Models\TauxDeChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalTvaTest extends TestCase
{
    use RefreshDatabase;

    public function test_recette_cdf_avec_tva_cree_trois_ecritures_en_attente_equilibrees(): void
    {
        [$user, $journalType, $operation] = $this->contexte();
        $tva = $this->compte($user, '443210', 'TVA facturée', 'Passif');
        ParametrageComptable::create(['user_id' => $user->id, 'code' => 'TVA_FACTUREE', 'designation' => 'TVA facturée', 'liste_des_comptes_id' => $tva->id]);

        $this->actingAs($user)->post('/journaux', $this->donnees($journalType, $operation, [
            'type' => 'recette', 'monnaie' => 'CDF', 'montant_ttc' => 116, 'appliquer_tva' => 1, 'taux_tva' => 16,
        ]))->assertRedirect(route('journaux.index'));

        $journal = Journaux::latest('id')->firstOrFail();
        $lignes = EcritureComptable::where('journal_id', $journal->id)->get();
        $this->assertCount(3, $lignes);
        $this->assertSame(['En attente'], $lignes->pluck('statut')->unique()->values()->all());
        $this->assertEquals(116.0, $lignes->sum('debit_cdf'));
        $this->assertEquals(116.0, $lignes->sum('credit_cdf'));
        $this->assertEquals(16.0, (float) $lignes->firstWhere('liste_des_comptes_id', $tva->id)->credit_cdf);
        $this->assertEquals(100.0, (float) $journal->montant_ht);
        $this->assertEquals(16.0, (float) $journal->montant_tva);
        $this->assertEquals(16.0, (float) $journal->taux_tva);
        $this->actingAs($user)->get(route('journaux.recu', $journal->id))
            ->assertOk()
            ->assertSee('TVA (16,00 %)')
            ->assertSee('100,00')
            ->assertSee('116,00');
    }

    public function test_depense_usd_avec_tva_est_convertie_en_cdf_et_reste_equilibree(): void
    {
        [$user, $journalType, $operation] = $this->contexte();
        $tva = $this->compte($user, '445110', 'TVA récupérable', 'Actif');
        ParametrageComptable::create(['user_id' => $user->id, 'code' => 'TVA_RECUPERABLE', 'designation' => 'TVA récupérable', 'liste_des_comptes_id' => $tva->id]);

        $this->actingAs($user)->post('/journaux', $this->donnees($journalType, $operation, [
            'type' => 'achat', 'monnaie' => 'USD', 'montant_ttc' => 116, 'appliquer_tva' => 1, 'taux_tva' => 16,
        ]))->assertRedirect(route('journaux.index'));

        $lignes = EcritureComptable::where('journal_id', Journaux::latest('id')->value('id'))->get();
        $this->assertCount(3, $lignes);
        $this->assertEquals(290000.0, $lignes->sum('debit_cdf'));
        $this->assertEquals(290000.0, $lignes->sum('credit_cdf'));
        $this->assertEquals(40000.0, (float) $lignes->firstWhere('liste_des_comptes_id', $tva->id)->debit_cdf);
        $this->assertEquals(116.0, (float) Journaux::latest('id')->value('sorties_usd'));
    }

    public function test_sans_tva_ne_recherche_aucun_parametrage_et_cree_deux_ecritures(): void
    {
        [$user, $journalType, $operation] = $this->contexte();

        $this->actingAs($user)->post('/journaux', $this->donnees($journalType, $operation, [
            'type' => 'vente', 'monnaie' => 'CDF', 'montant_ttc' => 100, 'appliquer_tva' => 0, 'taux_tva' => null,
        ]))->assertRedirect(route('journaux.index'));

        $lignes = EcritureComptable::where('journal_id', Journaux::latest('id')->value('id'))->get();
        $this->assertCount(2, $lignes);
        $this->assertEquals(100.0, $lignes->sum('debit_cdf'));
        $this->assertEquals(100.0, $lignes->sum('credit_cdf'));
    }

    public function test_avec_tva_sans_compte_configure_est_refuse_sans_ecriture(): void
    {
        [$user, $journalType, $operation] = $this->contexte();

        $this->actingAs($user)->from('/journaux/create')->post('/journaux', $this->donnees($journalType, $operation, [
            'type' => 'recette', 'monnaie' => 'CDF', 'montant_ttc' => 116, 'appliquer_tva' => 1, 'taux_tva' => 16,
        ]))->assertRedirect('/journaux/create')->assertSessionHasErrors('appliquer_tva');

        $this->assertDatabaseCount('journaux', 0);
        $this->assertDatabaseCount('ecritures_comptables', 0);
    }

    private function contexte(): array
    {
        $role = Role::create(['designation' => 'Comptable']);
        $user = User::create(['nom' => 'Test', 'prenom' => 'Comptable', 'email' => uniqid().'@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => 0, 'statut' => 'Actif']);
        $tresorerie = $this->compte($user, '571100', 'Caisse', 'Actif');
        $operation = $this->compte($user, '706100', 'Opération', 'Produit');
        $journalType = JournalType::create(['user_id' => $user->id, 'code' => 'CAI', 'libelle' => 'Caisse', 'liste_des_comptes_id' => $tresorerie->id, 'nature' => 'caisse', 'est_tresorerie' => true]);
        TauxDeChange::create(['user_id' => $user->id, 'taux_de_change' => 2500]);
        return [$user, $journalType, $operation];
    }

    private function compte(User $user, string $numero, string $designation, string $nature): ListeDesComptes
    {
        return ListeDesComptes::create(['user_id' => $user->id, 'compte' => $numero, 'designation' => $designation, 'nature' => $nature]);
    }

    private function donnees(JournalType $journalType, ListeDesComptes $operation, array $surcharge): array
    {
        return array_merge([
            'journal_type_id' => $journalType->id, 'liste_des_comptes_id' => $operation->id,
            'date' => '2026-07-27', 'type' => 'recette', 'monnaie' => 'CDF',
            'montant_ttc' => 100, 'appliquer_tva' => 0, 'taux_tva' => null,
            'mode_paiement' => 'espèces', 'nom_partenaire' => 'Client',
            'telephone_partenaire' => '000', 'adresse_partenaire' => 'Kinshasa',
            'description' => 'Opération de test',
        ], $surcharge);
    }
}
