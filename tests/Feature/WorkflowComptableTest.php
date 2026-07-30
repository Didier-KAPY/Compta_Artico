<?php

namespace Tests\Feature;

use App\Models\EcritureComptable;
use App\Models\EntreeCaisse;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\User;
use App\Services\WorkflowComptableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_journal_ne_peut_pas_etre_reouvert_si_une_ecriture_est_validee(): void
    {
        [$user, $journal, $compte] = $this->contexte('Super Admin', 'Validé');
        $this->actingAs($user);
        $ecriture = $this->ecriture($user, $journal, $compte);
        $ecriture->update(['statut' => 'Validé', 'valide_par' => $user->id, 'date_validation' => now()]);

        $this->expectException(ValidationException::class);
        app(WorkflowComptableService::class)->reouvrirJournal($journal);
    }

    public function test_roles_de_validation_sont_strictement_appliques_par_les_policies(): void
    {
        [$comptable, $journal, $compte] = $this->contexte('Comptable');
        $ecriture = $this->ecriture($comptable, $journal, $compte);

        $this->assertTrue($comptable->can('valider', $ecriture));
        $this->assertFalse($comptable->can('valider', $journal));

        [$caissier] = $this->contexte('Caissier');
        $this->assertTrue($caissier->can('valider', $journal));
        $this->assertFalse($caissier->can('valider', $ecriture));
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
            'compte' => '571100',
            'designation' => 'Caisse test',
        ]);
        $type = JournalType::create([
            'user_id' => $user->id,
            'code' => 'CAI',
            'libelle' => 'Journal test',
            'liste_des_comptes_id' => $compte->id,
            'nature' => 'caisse',
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
            'reference' => 'JRN-'.str()->uuid(),
            'date' => now()->toDateString(),
            'type' => 'recette',
            'monnaie' => 'CDF',
            'mode_paiement' => 'especes',
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
