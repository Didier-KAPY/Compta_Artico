<?php

namespace Tests\Feature;

use App\Models\BRC;
use App\Models\EcritureComptable;
use App\Models\EntreeCaisse;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\TauxDeChange;
use App\Models\SortieCaisse;
use App\Models\User;
use App\Services\WorkflowComptableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkflowComptableTest extends TestCase
{
    use RefreshDatabase;

    public function test_comptable_peut_valider_une_ecriture_une_seule_fois(): void
    {
        [$user, $journal, $compte] = $this->contexte('Comptable');
        $this->actingAs($user);
        $ecriture = $this->ecriture($user, $journal, $compte);
        $workflow = app(WorkflowComptableService::class);

        $workflow->validerEcriture($ecriture);

        $this->assertDatabaseHas('ecritures_comptables', [
            'id' => $ecriture->id,
            'statut' => 'Validé',
            'valide_par' => $user->id,
        ]);

        $this->expectException(ValidationException::class);
        $workflow->validerEcriture($ecriture);
    }

    public function test_valider_un_journal_ne_valide_pas_ses_ecritures(): void
    {
        [$user, $journal, $compte] = $this->contexte('Caissier');
        $this->actingAs($user);
        $ecriture = $this->ecriture($user, $journal, $compte);

        app(WorkflowComptableService::class)->validerJournal($journal);

        $this->assertSame('Validé', $journal->fresh()->statut);
        $this->assertSame('En attente', $ecriture->fresh()->statut);
        $this->assertNull($ecriture->fresh()->valide_par);
        $this->assertNull($ecriture->fresh()->date_validation);
    }

    public function test_reopening_journal_deletes_its_accounting_entries(): void
    {
        [$user, $journal, $compte] = $this->contexte('Super Admin', 'Validé');
        $this->actingAs($user);
        $ecriture = $this->ecriture($user, $journal, $compte);
        $ecriture->update(['statut' => 'Validé', 'valide_par' => $user->id, 'date_validation' => now()]);

        app(WorkflowComptableService::class)->reouvrirJournal($journal);

        $this->assertSame('En attente', $journal->fresh()->statut);
        $this->assertSoftDeleted($ecriture);
    }

    public function test_only_super_admin_can_open_reopen_and_reject_a_processed_journal(): void
    {
        [$user, $journal] = $this->contexte('Super Admin', 'Validé');

        $this->actingAs($user)->get(route('journaux.show', $journal))
            ->assertOk()
            ->assertSee('Remettre en attente')
            ->assertSee('Rejeter');

        $this->actingAs($user)->patch(route('journaux.reouvrir', $journal))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('En attente', $journal->fresh()->statut);

        $this->actingAs($user)->post(route('journaux.rejeter', $journal))
            ->assertSessionHasErrors('observation');
        $this->assertSame('En attente', $journal->fresh()->statut);

        $this->actingAs($user)->post(route('journaux.rejeter', $journal), [
            'observation' => 'Pièce justificative non conforme.',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('Rejeté', $journal->fresh()->statut);
        $this->assertSame('Pièce justificative non conforme.', $journal->fresh()->observation);

        foreach (['Admin', 'Directeur Général', 'Gérant'] as $designation) {
            [$manager, $restrictedJournal] = $this->contexte($designation, 'Validé');
            $this->actingAs($manager)->get(route('journaux.show', $restrictedJournal))->assertForbidden();
            $this->actingAs($manager)->patch(route('journaux.reouvrir', $restrictedJournal))->assertForbidden();
            $this->actingAs($manager)->post(route('journaux.rejeter', $restrictedJournal))->assertForbidden();
        }
    }

    public function test_roles_de_validation_sont_strictement_appliques_par_les_policies(): void
    {
        [$comptable, $journal, $compte] = $this->contexte('Comptable');
        $ecriture = $this->ecriture($comptable, $journal, $compte);

        $this->assertTrue($comptable->can('valider', $ecriture));
        $this->assertTrue($comptable->can('valider', $journal));

        [$caissier] = $this->contexte('Caissier');
        $this->assertTrue($caissier->can('valider', $journal));
        $this->assertFalse($caissier->can('valider', $ecriture));
    }

    public function test_journal_modal_only_displays_observation_for_processing(): void
    {
        [$user, $journal] = $this->contexte('Super Admin');

        $this->actingAs($user)->get(route('journaux.show', $journal))
            ->assertOk()
            ->assertSee('Nom du client / partenaire')
            ->assertSee('Téléphone')
            ->assertSee('Adresse')
            ->assertSee('Observation')
            ->assertSee('L’observation est obligatoire uniquement en cas de rejet.')
            ->assertDontSee('Type de journal')
            ->assertDontSee('id="liste_des_comptes_id_modal"', false);
    }

    public function test_journal_validation_creates_balanced_pending_double_entry(): void
    {
        [$user, $journal, $compteTresorerie] = $this->contexte('Super Admin');
        $compteOperation = ListeDesComptes::create([
            'user_id' => $user->id,
            'compte' => '701100',
            'designation' => 'Ventes de marchandises',
            'nature' => 'Produit',
        ]);

        $journal->update(['liste_des_comptes_id' => $compteOperation->id]);

        $this->actingAs($user)->post(route('journaux.valider', $journal))
            ->assertRedirect()->assertSessionHas('success');

        $lignes = EcritureComptable::where('journal_id', $journal->id)->get();
        $this->assertSame('Validé', $journal->fresh()->statut);
        $this->assertCount(1, $lignes);
        $this->assertSame('En attente', $lignes->first()->statut);
        $this->assertEquals(100.0, $lignes->sum('debit_cdf'));
        $this->assertEquals(0.0, $lignes->sum('credit_cdf'));
        $this->assertEquals($compteTresorerie->id, $lignes->first()->liste_des_comptes_id);

        $this->actingAs($user)->get(route('ecritures.show', $lignes->first()))
            ->assertOk()
            ->assertSee('La contrepartie n’est pas encore affichée')

            ->assertDontSee('Montant TVA inclus')
            ->assertDontSee('et de la TVA doit équilibrer');

        $this->actingAs($user)->post(route('ecritures.valider', $lignes->first()), [
            'liste_des_comptes_id' => $compteOperation->id,
        ])->assertRedirect()->assertSessionHas('success');

        $lignes = EcritureComptable::where('journal_id', $journal->id)->get();
        $this->assertCount(2, $lignes);
        $this->assertEquals(100.0, $lignes->sum('debit_cdf'));
        $this->assertEquals(100.0, $lignes->sum('credit_cdf'));
        $this->assertEquals(100.0, (float) $lignes->firstWhere('liste_des_comptes_id', $compteOperation->id)->credit_cdf);
    }

    public function test_usd_journal_is_converted_with_current_rate_before_creating_entries(): void
    {
        [$user, $journal] = $this->contexte('Super Admin');
        $compteOperation = ListeDesComptes::create([
            'user_id' => $user->id,
            'compte' => '701200',
            'designation' => 'Produit en USD',
            'nature' => 'Produit',
        ]);
        $compteCaisseUsd = ListeDesComptes::create([
            'user_id' => $user->id,
            'compte' => '571200',
            'designation' => 'Caisse USD',
            'nature' => 'Actif',
        ]);
        $typeCaisseUsd = JournalType::create([
            'user_id' => $user->id,
            'code' => 'CAI-USD',
            'libelle' => 'Caisse USD',
            'nature' => 'caisse',
            'monnaie' => 'USD',
            'est_tresorerie' => true,
            'liste_des_comptes_id' => $compteCaisseUsd->id,
        ]);
        TauxDeChange::create(['user_id' => $user->id, 'taux_de_change' => 2800]);
        $journal->update([
            'monnaie' => 'USD',
            'entrees_cdf' => 0,
            'entrees_usd' => 100,
            'montant_ttc' => 100,
        ]);

        $journal->update(['liste_des_comptes_id' => $compteOperation->id]);

        $this->actingAs($user)->post(route('journaux.valider', $journal))
            ->assertRedirect()->assertSessionHas('success');

        $lignes = EcritureComptable::where('journal_id', $journal->id)->get();
        $this->assertCount(1, $lignes);
        $this->assertSame($typeCaisseUsd->id, $journal->fresh()->journal_type_id);
        $this->assertSame('En attente', $lignes->first()->statut);
        $this->assertEquals($compteCaisseUsd->id, $lignes->first()->liste_des_comptes_id);
        $this->assertEquals(280000.0, $lignes->sum('debit_cdf'));
        $this->assertEquals(0.0, $lignes->sum('credit_cdf'));
    }

    public function test_bsc_reference_credits_treasury_and_debits_counterpart(): void
    {
        [$user, $journal, $compteTresorerie] = $this->contexte('Super Admin');
        $compteOperation = ListeDesComptes::create([
            'user_id' => $user->id,
            'compte' => '601100',
            'designation' => 'Achats consommés',
            'nature' => 'Charge',
        ]);
        $sortie = SortieCaisse::create([
            'user_id' => $user->id,
            'numero' => 'BSC-TEST-SORTIE',
            'date' => now()->toDateString(),
            'beneficiaire' => 'Fournisseur',
            'motif' => 'Achat',
            'montant' => 150,
            'monnaie' => 'CDF',
            'statut' => 'Validé',
        ]);
        $journal->update([
            'entree_caisse_id' => null,
            'sortie_caisse_id' => $sortie->id,
            'reference' => $sortie->numero,
            'entrees_cdf' => 0,
            'sorties_cdf' => 150,
        ]);

        $journal->update(['liste_des_comptes_id' => $compteOperation->id]);

        $this->actingAs($user)->post(route('journaux.valider', $journal))
            ->assertRedirect()->assertSessionHas('success');

        $lignes = EcritureComptable::where('journal_id', $journal->id)->get();
        $this->assertCount(1, $lignes);
        $this->assertEquals($compteTresorerie->id, $lignes->first()->liste_des_comptes_id);
        $this->assertEquals(0.0, $lignes->sum('debit_cdf'));
        $this->assertEquals(150.0, $lignes->sum('credit_cdf'));
    }

    public function test_accounting_entries_list_displays_most_recent_first(): void
    {
        [$user, $journal] = $this->contexte('Super Admin');

        foreach (['Ancienne écriture', 'Écriture récente 1', 'Écriture récente 2'] as $index => $designation) {
            $compte = ListeDesComptes::create([
                'user_id' => $user->id,
                'compte' => '70'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'designation' => $designation,
                'nature' => 'Produit',
            ]);
            EcritureComptable::create([
                'user_id' => $user->id,
                'journal_id' => $journal->id,
                'liste_des_comptes_id' => $compte->id,
                'date' => now()->toDateString(),
                'libelle' => $designation,
                'debit_cdf' => 100,
                'credit_cdf' => 0,
                'statut' => 'Validé',
                'valide_par' => $user->id,
                'date_validation' => now(),
            ]);
        }

        $this->actingAs($user)->get(route('ecritures.liste'))
            ->assertOk()
            ->assertSeeInOrder(['Écriture récente 2', 'Écriture récente 1', 'Ancienne écriture']);
    }

    public function test_bsc_entry_requires_supporting_document_before_validation(): void
    {
        Storage::fake('public');
        [$user, $journal, $compte] = $this->contexte('Comptable');
        $ecriture = $this->ecriture($user, $journal, $compte);
        $ecriture->update(['piece' => 'BSC-TEST-JUSTIFICATIF']);
        $opposee = $this->ecriture($user, $journal, $compte);
        $opposee->update(['piece' => 'BSC-TEST-JUSTIFICATIF', 'debit_cdf' => 0, 'credit_cdf' => 100]);

        $this->actingAs($user)->post(route('ecritures.valider', $ecriture), [
            'liste_des_comptes_id' => $compte->id,
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('piece_justificative');
        $this->assertSame('En attente', $ecriture->fresh()->statut);

        $this->actingAs($user)->post(route('ecritures.valider', $ecriture), [
            'liste_des_comptes_id' => $compte->id,
            'piece_justificative' => UploadedFile::fake()->create('facture.pdf', 100, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('success');

        $ecriture->refresh();
        $this->assertSame('Validé', $ecriture->statut);
        $this->assertNotNull($ecriture->piece_justificative);
        $this->assertSame($ecriture->piece_justificative, $opposee->fresh()->piece_justificative);
        Storage::disk('public')->assertExists($ecriture->piece_justificative);

        $this->actingAs($user)->get(route('ecritures.liste'))
            ->assertOk()
            ->assertSee('Imputation')
            ->assertDontSee(route('ecritures.piece', $ecriture), false);

        $this->actingAs($user)->get(route('ecritures.piece', $ecriture))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="'.basename($ecriture->piece_justificative).'"');
    }

    public function test_validated_journal_is_imputed_then_moved_to_validated_entries(): void
    {
        Storage::fake('public');
        [$user, $journal, $treasury] = $this->contexte('Comptable', 'Validé');
        $journal->update(['reference' => 'BSM-TEST-001']);
        $operation = ListeDesComptes::create([
            'user_id' => $user->id,
            'compte' => '701999',
            'designation' => 'Produit à imputer',
            'nature' => 'Produit',
        ]);
        $debit = $this->ecriture($user, $journal, $treasury);
        $credit = $this->ecriture($user, $journal, $operation);
        $credit->update(['debit_cdf' => 0, 'credit_cdf' => 100]);
        $brc = BRC::create([
            'user_id' => $user->id,
            'journal_type_id' => $journal->journal_type_id,
            'journal_id' => $journal->id,
            'reference' => 'BRC-TEST-001',
            'date' => today(),
            'monnaie' => 'CDF',
            'sens' => 'debit',
            'total' => 100,
            'statut' => 'Validé',
            'valide_par' => $user->id,
            'date_validation' => now(),
        ]);
        $brc->journaux()->attach($journal->id);

        $this->actingAs($user)->get(route('comptabilite.imputation-compte', ['journal_id' => $journal->id]))
            ->assertOk()
            ->assertSee('Journal des opérations diverses')
            ->assertSee($journal->reference)
            ->assertSee('Voir')
            ->assertDontSee('Compte d’imputation');

        $payload = ['comptes' => [$debit->id => $treasury->id, $credit->id => $operation->id]];
        $this->actingAs($user)->post(route('comptabilite.imputation-compte.traiter', $journal), $payload)
            ->assertSessionHasErrors('piece_justificative');

        $payload['piece_justificative'] = UploadedFile::fake()->create('justificatif.pdf', 100, 'application/pdf');
        $this->actingAs($user)->post(route('comptabilite.imputation-compte.traiter', $journal), $payload)
            ->assertRedirect(route('ecritures.liste', ['journal_id' => $journal->id]))
            ->assertSessionHas('success');

        $this->assertSame('Validé', $debit->fresh()->statut);
        $this->assertSame('Validé', $credit->fresh()->statut);
        $this->assertNotNull($debit->fresh()->piece_justificative);

        $this->actingAs($user)->get(route('ecritures.liste', ['journal_id' => $journal->id]))
            ->assertOk()
            ->assertSee('Écriture test');
    }
    public function test_une_validation_valide_toutes_les_ecritures_de_la_meme_reference(): void
    {
        [$user, $journal, $compte] = $this->contexte('Comptable', 'Validé');
        $premiere = $this->ecriture($user, $journal, $compte);
        $seconde = $this->ecriture($user, $journal, $compte);
        $premiere->update(['piece' => 'BRC-GROUPE-001']);
        $seconde->update(['piece' => 'BRC-GROUPE-001', 'debit_cdf' => 0, 'credit_cdf' => 100]);

        $this->actingAs($user)->post(route('ecritures.valider', $premiere), [
            'liste_des_comptes_id' => $compte->id,
        ])->assertRedirect();

        $this->assertSame('Validé', $premiere->fresh()->statut);
        $this->assertSame('Validé', $seconde->fresh()->statut);
        $this->assertSame($user->id, $seconde->fresh()->valide_par);
    }
    private function contexte(string $roleName, string $journalStatus = 'En attente'): array
    {
        $role = Role::create(['designation' => $roleName]);
        $user = User::create([
            'nom' => 'Test',
            'prenom' => $roleName,
            'email' => str()->uuid().'@example.test',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'statut' => 'Actif',
        ]);
        $compte = ListeDesComptes::create([
            'user_id' => $user->id,
            'compte' => sprintf('57%04d', $user->id),
            'designation' => 'Caisse test',
        ]);
        $type = JournalType::create([
            'user_id' => $user->id,
            'code' => 'CAI',
            'libelle' => 'Journal test',
            'liste_des_comptes_id' => $compte->id,
            'nature' => 'caisse',
            'monnaie' => 'CDF',
            'est_tresorerie' => true,
        ]);
        $entree = EntreeCaisse::create([
            'user_id' => $user->id,
            'numero' => 'BEC-'.str()->uuid(),
            'date' => now()->toDateString(),
            'motif' => 'Test',
            'montant' => 100,
            'monnaie' => 'CDF',
            'statut' => 'Validé',
            'type' => 'Caisse',
        ]);
        $journal = Journaux::create([
            'user_id' => $user->id,
            'journal_type_id' => $type->id,
            'liste_des_comptes_id' => $compte->id,
            'entree_caisse_id' => $entree->id,
            'reference' => 'BEC-'.str()->uuid(),
            'date' => now()->toDateString(),
            'type' => 'recette',
            'monnaie' => 'CDF',
            'mode_paiement' => 'espèces',
            'montant_ttc' => 100,
            'entrees_cdf' => 100,
            'statut' => $journalStatus,
        ]);

        return [$user, $journal, $compte];
    }

    private function ecriture(User $user, Journaux $journal, ListeDesComptes $compte): EcritureComptable
    {
        return EcritureComptable::create([
            'user_id' => $user->id,
            'journal_id' => $journal->id,
            'liste_des_comptes_id' => $compte->id,
            'date' => now()->toDateString(),
            'libelle' => 'Écriture test',
            'debit_cdf' => 100,
            'credit_cdf' => 0,
            'statut' => 'En attente',
            'valide_par' => null,
            'date_validation' => null,
        ]);
    }
}
