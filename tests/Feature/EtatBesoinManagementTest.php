<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EtatBesoin;
use App\Models\EcritureComptable;
use App\Models\Journaux;
use App\Models\JournalType;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\SortieCaisse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtatBesoinManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_roles_can_only_view_and_validate_an_etat(): void
    {
        foreach (['Admin', 'Gérant', 'Gerant', 'Directeur Général'] as $index => $designation) {
            $role = Role::create(['designation' => $designation]);
            $user = $this->user($role, $index);
            $departement = Departement::create(['designation' => 'Service '.$index]);
            $etat = $this->etat($user, $departement, 'EB-M-'.$index, 'En attente');

            $this->actingAs($user)->put(route('etat-besoins.update', $etat), [
                'departement_id' => $departement->id,
                'demandeur' => 'Demandeur modifié '.$index,
            ])->assertForbidden();

            foreach (['rejeter', 'attente'] as $action) {
                $this->actingAs($user)->from(route('etat-besoins.show', $etat))->post(route('etat-besoins.valider', $etat), [
                    'observation' => 'Action interdite', 'action' => $action, 'monnaie' => 'CDF',
                ])->assertForbidden();
            }

            $this->actingAs($user)->from(route('etat-besoins.show', $etat))->post(route('etat-besoins.valider', $etat), [
                'observation' => 'Validation autorisée', 'action' => 'valider', 'monnaie' => 'CDF',
            ])->assertRedirect(route('etat-besoins.show', $etat));

            $this->assertSame('Validé', $etat->fresh()->statut);
            $this->assertSame($user->id, $etat->fresh()->valide_par);
            $this->assertDatabaseHas('sortie_caisses', ['etat_besoin_id' => $etat->id, 'statut' => 'En attente']);
            $sortie = SortieCaisse::where('etat_besoin_id', $etat->id)->firstOrFail();
            $this->assertNull($sortie->numero);
            $this->assertNull($sortie->type_bon);
            $this->actingAs($user)->get(route('sortie-caisses.show', $sortie))
                ->assertOk()->assertSee('Nature du bon')->assertSee('Non attribué');
        }
    }

    public function test_super_admin_can_reopen_an_etat_without_validated_output(): void
    {
        $role = Role::create(['designation' => 'Super Admin']);
        $user = $this->user($role, 20);
        $departement = Departement::create(['designation' => 'Direction']);
        $etat = $this->etat($user, $departement, 'EB-REOPEN', 'Validé');
        $sortie = $this->sortie($user, $etat, 'BSC-REOPEN', 'En attente');

        $this->actingAs($user)->patch(route('etat-besoins.reouvrir', $etat))->assertRedirect();
        $this->assertSame('En attente', $etat->fresh()->statut);
        $this->assertSoftDeleted($sortie);
    }

    public function test_super_admin_can_delete_an_etat_and_its_entire_accounting_chain(): void
    {
        $role = Role::create(['designation' => 'Super Admin']);
        $user = $this->user($role, 21);
        $departement = Departement::create(['designation' => 'Suppression']);
        $etat = $this->etat($user, $departement, 'EB-DELETE', 'Validé');
        $sortie = $this->sortie($user, $etat, 'BSC-DELETE', 'Validé');
        $compte = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '570001', 'designation' => 'Caisse']);
        $type = JournalType::create(['user_id' => $user->id, 'code' => 'CAI', 'libelle' => 'Caisse', 'liste_des_comptes_id' => $compte->id]);
        $journal = Journaux::create([
            'user_id' => $user->id, 'journal_type_id' => $type->id, 'liste_des_comptes_id' => $compte->id,
            'sortie_caisse_id' => $sortie->id, 'reference' => $sortie->numero, 'date' => now(),
            'description' => 'Chaîne à supprimer', 'type' => 'depense', 'monnaie' => 'CDF',
            'mode_paiement' => 'espèces', 'sorties_cdf' => 100, 'statut' => 'Validé',
        ]);
        $ecriture = EcritureComptable::create([
            'user_id' => $user->id, 'journal_id' => $journal->id, 'liste_des_comptes_id' => $compte->id,
            'date' => now(), 'piece' => $sortie->numero, 'libelle' => 'Chaîne à supprimer',
            'debit_cdf' => 100, 'credit_cdf' => 0, 'statut' => 'Validé',
        ]);

        $this->actingAs($user)->get(route('etat-besoins.show', $etat))
            ->assertOk()
            ->assertSee(route('etat-besoins.destroy', $etat), false)
            ->assertSee('Supprimer');

        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat), [
            'motif' => 'Correction comptable complète demandée par la direction.',
            'strategie' => 'cascade',
            'confirmation_comptable' => '1',
        ])
            ->assertRedirect(route('etat-besoins.index'));

        $this->assertSoftDeleted($etat);
        $this->assertSoftDeleted($sortie);
        $this->assertSoftDeleted($journal);
        $this->assertSoftDeleted($ecriture);
    }

    public function test_non_super_admin_cannot_see_or_use_etat_deletion(): void
    {
        $role = Role::create(['designation' => 'Comptable']);
        $user = $this->user($role, 22);
        $departement = Departement::create(['designation' => 'Comptabilité']);
        $etat = $this->etat($user, $departement, 'EB-PROTECTED', 'Validé');

        $this->actingAs($user)->get(route('etat-besoins.show', $etat))
            ->assertOk()
            ->assertDontSee(route('etat-besoins.destroy', $etat), false);

        $this->actingAs($user)->delete(route('etat-besoins.destroy', $etat))
            ->assertForbidden();

        $this->assertModelExists($etat);
    }

    private function user(Role $role, int $index): User
    {
        return User::create(['nom' => 'Test', 'prenom' => 'Gestion', 'email' => 'gestion'.$index.'@test.local',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif']);
    }

    private function etat(User $user, Departement $departement, string $numero, string $statut): EtatBesoin
    {
        return EtatBesoin::create(['user_id' => $user->id, 'departement_id' => $departement->id, 'numero' => $numero,
            'date' => now(), 'service' => $departement->designation, 'demandeur' => 'Initial', 'motif' => 'Test',
            'montant_estime' => 100, 'monnaie' => 'CDF', 'statut' => $statut]);
    }

    private function sortie(User $user, EtatBesoin $etat, string $numero, string $statut): SortieCaisse
    {
        return SortieCaisse::create(['user_id' => $user->id, 'etat_besoin_id' => $etat->id, 'numero' => $numero,
            'date' => now(), 'beneficiaire' => $etat->demandeur, 'motif' => 'Test', 'montant' => 100,
            'monnaie' => 'CDF', 'statut' => $statut]);
    }
}
