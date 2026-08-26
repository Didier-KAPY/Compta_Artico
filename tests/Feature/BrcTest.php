<?php

namespace Tests\Feature;

use App\Models\BRC;
use App\Models\EcritureComptable;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\TauxDeChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrcTest extends TestCase
{
    use RefreshDatabase;

    public function test_charge_des_finances_peut_ouvrir_et_enregistrer_un_brc(): void
    {
        [$user, $type, , $compteImputation] = $this->contexte('Chargé des finances');

        $this->actingAs($user)->get(route('brc.index'))
            ->assertOk()
            ->assertSee(route('brc.create'), false);

        $this->actingAs($user)->get(route('brc.create'))
            ->assertOk()
            ->assertSee('Nouveau BRC');

        $this->actingAs($user)->post(route('brc.store'), [
            'date' => '2026-08-04',
            'journal_type_id' => $type->id,
            'monnaie' => 'CDF',
            'sens' => 'debit',
            'lignes' => [[
                'compte_id' => $compteImputation->id,
                'libelle' => 'Regularisation finance',
                'montant' => 250,
            ]],
        ])->assertRedirect();

        $this->assertDatabaseCount('brcs', 1);
    }
    public function test_creation_genere_une_reference_brc_et_valide_toute_la_chaine(): void
    {
        [$user, $type, , $compteImputation] = $this->contexte();

        $this->actingAs($user)->post(route('brc.store'), [
            'date' => '2026-08-04', 'journal_type_id' => $type->id,
            'monnaie' => 'CDF', 'sens' => 'debit',
            'lignes' => [['compte_id' => $compteImputation->id, 'libelle' => 'Régularisation client', 'montant' => 250]],
        ])->assertRedirect(route('comptabilite.imputation-compte', ['journal_id' => 1]));

        $brc = BRC::with('lignes')->firstOrFail();
        $this->assertSame('BRC-20260804-000001', $brc->reference);
        $this->assertSame('Validé', $brc->statut);
        $this->assertSame(250.0, (float) $brc->total);
        $this->assertCount(1, $brc->lignes);
        $this->assertDatabaseCount('journaux', 1);
        $this->assertDatabaseCount('ecritures_comptables', 2);

        $page = $this->actingAs($user)->get(route('comptabilite.imputation-compte', [
            'date_debut' => '2026-08-04', 'date_fin' => '2026-08-04',
            'reference' => $brc->reference,
        ]));
        $page->assertOk()->assertSee('Journal des opérations diverses')->assertSee('Compte débit')->assertSee('Compte crédit')
            ->assertSee('Numéro de référence')->assertSee('Date début')->assertSee('Date fin')
            ->assertSee($brc->reference)->assertDontSee('<th>Journal</th>', false);
    }

    public function test_creation_accepte_et_propage_une_piece_justificative(): void
    {
        Storage::fake('public');
        [$user, $type, , $compteImputation] = $this->contexte();

        $this->actingAs($user)->post(route('brc.store'), [
            'date' => '2026-08-04',
            'journal_type_id' => $type->id,
            'monnaie' => 'CDF',
            'sens' => 'debit',
            'piece_justificative' => UploadedFile::fake()->create('justificatif.pdf', 100, 'application/pdf'),
            'lignes' => [[
                'compte_id' => $compteImputation->id,
                'libelle' => 'Regularisation client',
                'montant' => 250,
            ]],
        ])->assertRedirect();

        $brc = BRC::firstOrFail();
        $this->assertNotNull($brc->piece_justificative);
        Storage::disk('public')->assertExists($brc->piece_justificative);
        $this->assertSame($brc->piece_justificative, Journaux::firstOrFail()->piece_justificatif);
        $this->assertSame(2, EcritureComptable::where('piece_justificative', $brc->piece_justificative)->count());
        $this->actingAs($user)->get(route('brc.piece', $brc))->assertOk();
    }
    public function test_validation_cree_un_journal_et_des_ecritures_equilibrees_valides(): void
    {
        [$user, $type, $compteJournal, $compteImputation] = $this->contexte();
        $this->actingAs($user)->post(route('brc.store'), [
            'date' => '2026-08-04', 'journal_type_id' => $type->id,
            'monnaie' => 'CDF', 'sens' => 'debit',
            'lignes' => [['compte_id' => $compteImputation->id, 'libelle' => 'Régularisation client', 'montant' => 250]],
        ]);

        $brc = BRC::firstOrFail();
        $this->actingAs($user)->from(route('brc.index'))->post(route('brc.valider', $brc))
            ->assertRedirect(route('brc.index'));

        $journal = Journaux::firstOrFail();
        $this->assertSame('Validé', $journal->statut);
        $this->assertSame($brc->reference, $journal->reference);
        $this->assertSame('Validé', $brc->fresh()->statut);
        $this->assertSame($journal->id, $brc->fresh()->journal_id);
        $this->assertDatabaseHas('brc_journal', ['brc_id' => $brc->id, 'journal_id' => $journal->id]);

        $journalLine = EcritureComptable::where('liste_des_comptes_id', $compteJournal->id)->firstOrFail();
        $imputation = EcritureComptable::where('liste_des_comptes_id', $compteImputation->id)->firstOrFail();
        $this->assertSame('Validé', $journalLine->statut);
        $this->assertSame('Validé', $imputation->statut);
        $this->assertSame(250.0, (float) $journalLine->debit_cdf);
        $this->assertSame(250.0, (float) $imputation->credit_cdf);

        $this->actingAs($user)->get(route('brc.show', $brc))
            ->assertOk();
        $this->actingAs($user)->get(route('journaux.show', $journal))
            ->assertOk()
            ->assertSee('Voir le BRC');
        $this->actingAs($user)->get(route('ecritures.show', $journalLine))
            ->assertOk()
            ->assertSee('Voir le BRC');
    }

    public function test_journal_od_affiche_la_monnaie_et_le_montant_originaux_du_brc_usd(): void
    {
        Storage::fake('public');
        [$user, $type, , $compteImputation] = $this->contexte();
        TauxDeChange::create(['user_id' => $user->id, 'taux_de_change' => 2500, 'date_taux' => '2026-08-04']);

        $this->actingAs($user)->post(route('brc.store'), [
            'date' => '2026-08-04', 'journal_type_id' => $type->id,
            'monnaie' => 'USD', 'sens' => 'debit',
            'lignes' => [['compte_id' => $compteImputation->id, 'libelle' => 'Régularisation USD', 'montant' => 10]],
        ]);

        $this->actingAs($user)->get(route('comptabilite.imputation-compte', [
            'date_debut' => '2026-08-04', 'date_fin' => '2026-08-04',
        ]))->assertOk()->assertSee('USD')->assertSee('10,00')->assertDontSee('25 000,00');

        $journal = Journaux::firstOrFail();
        $this->assertSame('USD', $journal->monnaie);
        $this->assertSame(10.0, (float) $journal->montant_ttc);
        $this->assertSame(2, EcritureComptable::where('journal_id', $journal->id)->where('statut', 'Validé')->count());
        $this->assertEquals(25000.0, EcritureComptable::where('journal_id', $journal->id)->sum('debit_cdf'));
    }
    private function contexte(string $roleDesignation = 'Comptable'): array
    {
        $role = Role::firstOrCreate(['designation' => $roleDesignation]);
        $user = User::create(['nom' => 'Test', 'prenom' => 'BRC', 'email' => uniqid().'@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => 0, 'statut' => 'Actif']);
        $compteJournal = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '471100', 'designation' => 'Compte OD', 'nature' => 'Passif']);
        $compteImputation = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '411100', 'designation' => 'Client', 'nature' => 'Actif']);
        $type = JournalType::create(['user_id' => $user->id, 'code' => 'OD', 'libelle' => 'Opérations diverses', 'liste_des_comptes_id' => $compteJournal->id, 'nature' => 'od', 'est_tresorerie' => false]);

        return [$user, $type, $compteJournal, $compteImputation];
    }
}
