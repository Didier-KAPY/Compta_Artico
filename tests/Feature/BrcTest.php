<?php

namespace Tests\Feature;

use App\Models\BRC;
use App\Models\EcritureComptable;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrcTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_genere_une_reference_brc_et_conserve_les_lignes_en_attente(): void
    {
        [$user, $type, , $compteImputation] = $this->contexte();

        $this->actingAs($user)->post(route('brc.store'), [
            'date' => '2026-08-04', 'journal_type_id' => $type->id,
            'monnaie' => 'CDF', 'sens' => 'debit',
            'lignes' => [['compte_id' => $compteImputation->id, 'libelle' => 'Régularisation client', 'montant' => 250]],
        ])->assertRedirect(route('brc.index'));

        $brc = BRC::with('lignes')->firstOrFail();
        $this->assertSame('BRC-20260804-000001', $brc->reference);
        $this->assertSame('En attente', $brc->statut);
        $this->assertSame(250.0, (float) $brc->total);
        $this->assertCount(1, $brc->lignes);
        $this->assertDatabaseCount('journaux', 0);
        $this->assertDatabaseCount('ecritures_comptables', 0);
    }

    public function test_validation_cree_un_journal_valide_et_des_ecritures_equilibrees_en_attente(): void
    {
        [$user, $type, $compteJournal, $compteImputation] = $this->contexte();
        $this->actingAs($user)->post(route('brc.store'), [
            'date' => '2026-08-04', 'journal_type_id' => $type->id,
            'monnaie' => 'CDF', 'sens' => 'debit',
            'lignes' => [['compte_id' => $compteImputation->id, 'libelle' => 'Régularisation client', 'montant' => 250]],
        ]);

        $brc = BRC::firstOrFail();
        $this->actingAs($user)->post(route('brc.valider', $brc))
            ->assertRedirect(route('brc.index'));

        $journal = Journaux::firstOrFail();
        $this->assertSame('Validé', $journal->statut);
        $this->assertSame($brc->reference, $journal->reference);
        $this->assertSame('Validé', $brc->fresh()->statut);
        $this->assertSame($journal->id, $brc->fresh()->journal_id);
        $this->assertDatabaseHas('brc_journal', ['brc_id' => $brc->id, 'journal_id' => $journal->id]);

        $journalLine = EcritureComptable::where('liste_des_comptes_id', $compteJournal->id)->firstOrFail();
        $imputation = EcritureComptable::where('liste_des_comptes_id', $compteImputation->id)->firstOrFail();
        $this->assertSame('En attente', $journalLine->statut);
        $this->assertSame('En attente', $imputation->statut);
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

    private function contexte(): array
    {
        $role = Role::create(['designation' => 'Comptable']);
        $user = User::create(['nom' => 'Test', 'prenom' => 'BRC', 'email' => uniqid().'@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => 0, 'statut' => 'Actif']);
        $compteJournal = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '471100', 'designation' => 'Compte OD', 'nature' => 'Passif']);
        $compteImputation = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '411100', 'designation' => 'Client', 'nature' => 'Actif']);
        $type = JournalType::create(['user_id' => $user->id, 'code' => 'OD', 'libelle' => 'Opérations diverses', 'liste_des_comptes_id' => $compteJournal->id, 'nature' => 'od', 'est_tresorerie' => false]);

        return [$user, $type, $compteJournal, $compteImputation];
    }
}
