<?php

namespace Tests\Feature;

use App\Models\EcritureComptable;
use App\Models\BRC;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\TauxDeChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcritureOdSensTest extends TestCase
{
    use RefreshDatabase;

    public function test_imputation_form_has_searchable_journal_and_account_fields(): void
    {
        [$user] = $this->contexte('recherche');

        $this->actingAs($user)->get(route('ecritures.create'))
            ->assertOk()
            ->assertSee('journal-search')
            ->assertSee('compte-search')
            ->assertSee('Monnaie')
            ->assertSee('USD')
            ->assertSee('Rechercher un journal')
            ->assertSee('Rechercher un compte');
    }

    public function test_les_deux_sens_sappliquent_au_compte_du_journal(): void
    {
        foreach (['debit' => [250, 0, 0, 250], 'credit' => [0, 250, 250, 0]] as $sens => $attendu) {
            [$user, $type, $compteJournal, $compteImputation] = $this->contexte($sens);

            $this->actingAs($user)->post(route('ecritures.store'), [
                'date' => '2026-07-29', 'journal_type_id' => $type->id,
                'monnaie' => 'CDF', 'sens' => $sens, 'description' => 'Régularisation',
                'lignes' => [['compte_id' => $compteImputation->id, 'libelle' => 'Imputation', 'montant' => 250]],
            ])->assertRedirect(route('ecritures.create'));

            $journal = EcritureComptable::where('liste_des_comptes_id', $compteJournal->id)->latest('id')->firstOrFail();
            $imputation = EcritureComptable::where('liste_des_comptes_id', $compteImputation->id)->latest('id')->firstOrFail();
            $this->assertSame('Imputation', $journal->libelle);
            $this->assertNotSame('Contrepartie', $journal->libelle);
            $this->assertEquals($attendu, [(float) $journal->debit_cdf, (float) $journal->credit_cdf, (float) $imputation->debit_cdf, (float) $imputation->credit_cdf]);

            $journalPrincipal = Journaux::where('liste_des_comptes_id', $compteJournal->id)->latest('id')->firstOrFail();
            $journalContrepartie = Journaux::where('liste_des_comptes_id', $compteImputation->id)->latest('id')->firstOrFail();
            $this->assertEquals($attendu, [
                (float) $journalPrincipal->entrees_cdf,
                (float) $journalPrincipal->sorties_cdf,
                (float) $journalContrepartie->entrees_cdf,
                (float) $journalContrepartie->sorties_cdf,
            ]);
            $this->assertSame($journalPrincipal->reference, $journalContrepartie->reference);
        }
    }

    public function test_imputation_usd_conserve_la_devise_et_convertit_les_ecritures_en_cdf(): void
    {
        [$user, $type, $compteJournal, $compteImputation] = $this->contexte('usd', 'USD');
        TauxDeChange::create(['user_id' => $user->id, 'taux_de_change' => 2250]);

        $this->actingAs($user)->post(route('ecritures.store'), [
            'date' => '2026-07-29', 'journal_type_id' => $type->id,
            'monnaie' => 'USD', 'sens' => 'debit', 'description' => 'Imputation USD',
            'lignes' => [['compte_id' => $compteImputation->id, 'libelle' => 'Imputation', 'montant' => 10]],
        ])->assertRedirect(route('ecritures.create'));

        $journal = Journaux::where('liste_des_comptes_id', $compteJournal->id)->latest('id')->firstOrFail();
        $this->assertSame('USD', $journal->monnaie);
        $this->assertSame(10.0, (float) $journal->entrees_usd);
        $this->assertSame(0.0, (float) $journal->entrees_cdf);

        $ecriture = EcritureComptable::where('liste_des_comptes_id', $compteJournal->id)->latest('id')->firstOrFail();
        $this->assertSame(22500.0, (float) $ecriture->debit_cdf);
    }

    public function test_imputation_cree_automatiquement_un_brc_valide_lie_aux_journaux(): void
    {
        [$user, $type, , $compteImputation] = $this->contexte('brc-auto');

        $this->actingAs($user)->post(route('ecritures.store'), [
            'date' => '2026-07-29',
            'journal_type_id' => $type->id,
            'monnaie' => 'CDF',
            'sens' => 'debit',
            'lignes' => [[
                'compte_id' => $compteImputation->id,
                'libelle' => 'Imputation avec BRC',
                'montant' => 250,
            ]],
        ])->assertRedirect(route('ecritures.create'));

        $brc = BRC::with(['lignes', 'journaux'])->firstOrFail();

        $this->assertSame('Validé', $brc->statut);
        $this->assertSame('imputation', $brc->origine);
        $this->assertSame('BRC-20260729-000001', $brc->reference);
        $this->assertSame(250.0, (float) $brc->total);
        $this->assertCount(1, $brc->lignes);
        $this->assertCount(2, $brc->journaux);
        $this->assertTrue($brc->journaux->every(fn (Journaux $journal) => $journal->reference === $brc->reference));
    }

    private function contexte(string $suffixe, string $monnaie = 'CDF'): array
    {
        $role = Role::firstOrCreate(['designation' => 'Comptable']);
        $user = User::create(['nom' => 'Test', 'prenom' => 'OD', 'email' => $suffixe.'@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => 0, 'statut' => 'Actif']);
        $compteJournal = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '4711'.$suffixe, 'designation' => 'Compte OD', 'nature' => 'Passif']);
        $compteImputation = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '4111'.$suffixe, 'designation' => 'Client', 'nature' => 'Actif']);
        $type = JournalType::create(['user_id' => $user->id, 'code' => 'OD'.$suffixe, 'libelle' => 'Opérations diverses', 'liste_des_comptes_id' => $compteJournal->id, 'nature' => 'od', 'monnaie' => $monnaie, 'est_tresorerie' => false]);
        return [$user, $type, $compteJournal, $compteImputation];
    }
}
