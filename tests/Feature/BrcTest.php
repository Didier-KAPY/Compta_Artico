<?php

namespace Tests\Feature;

use App\Models\EcritureComptable;
use App\Models\Journaux;
use App\Models\JournalType;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrcTest extends TestCase
{
    use RefreshDatabase;

    public function test_brc_affiche_chaque_ecriture_validee_et_conserve_les_references(): void
    {
        [$user, $type, $journal, $debit, $credit] = $this->contexte();
        $this->ecriture($user, $journal, $debit, '2026-07-10', 'Débit banque', 150, 0, 'CHQ-01');
        $this->ecriture($user, $journal, $credit, '2026-07-10', 'Crédit client', 0, 150, 'CHQ-01');
        $this->ecriture($user, $journal, $debit, '2026-07-11', 'Non validée', 99, 0, 'BROUILLON', 'En attente');

        $response = $this->actingAs($user)->get(route('ecritures.brc.generer', [
            'date_debut' => '2026-07-01', 'date_fin' => '2026-07-31', 'journal_type_id' => $type->id,
        ]));
        $response->assertOk()->assertSee('CHQ-01')->assertSee('Débit banque')
            ->assertSee('Crédit client')->assertDontSee('Non validée')
            ->assertSee('ECRITURE EQUILIBREE')->assertSee('150,00');

        $this->actingAs($user)->get(route('ecritures.brc.pdf', [
            'date_debut' => '2026-07-01', 'date_fin' => '2026-07-31', 'journal_type_id' => $type->id,
        ]))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_brc_refuse_une_periode_inversee(): void
    {
        [$user] = $this->contexte();
        $this->actingAs($user)->get(route('ecritures.brc.generer', [
            'date_debut' => '2026-07-31', 'date_fin' => '2026-07-01',
        ]))->assertSessionHasErrors('date_fin');
    }

    private function contexte(): array
    {
        $role = Role::create(['designation' => 'Admin']);
        $user = User::create(['nom' => 'Test', 'prenom' => 'BRC', 'email' => uniqid().'@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => 0, 'statut' => 'Actif']);
        $debit = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '521100', 'designation' => 'Banque', 'nature' => 'Actif']);
        $credit = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '411100', 'designation' => 'Client', 'nature' => 'Actif']);
        $type = JournalType::create(['user_id' => $user->id, 'code' => 'BQ', 'libelle' => 'Banque', 'liste_des_comptes_id' => $debit->id, 'nature' => 'banque', 'est_tresorerie' => true]);
        $journal = Journaux::create(['user_id' => $user->id, 'journal_type_id' => $type->id, 'liste_des_comptes_id' => $debit->id, 'reference' => 'BRC-JRN-01', 'date' => '2026-07-10', 'description' => 'Remise', 'statut' => 'Validé']);
        return [$user, $type, $journal, $debit, $credit];
    }

    private function ecriture(User $user, Journaux $journal, ListeDesComptes $compte, string $date, string $libelle, float $debit, float $credit, string $piece, string $statut = 'Validé'): void
    {
        EcritureComptable::create(['user_id' => $user->id, 'journal_id' => $journal->id, 'liste_des_comptes_id' => $compte->id, 'date' => $date, 'piece' => $piece, 'libelle' => $libelle, 'debit_cdf' => $debit, 'credit_cdf' => $credit, 'statut' => $statut]);
    }
}
