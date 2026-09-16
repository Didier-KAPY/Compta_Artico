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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EtatBesoinManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_plusieurs_pieces_sont_ajoutees_sans_remplacer_les_anciennes(): void
    {
        Storage::fake('public');
        $user = $this->user(Role::firstOrCreate(['designation' => 'Super Admin']), 90);
        $etat = $this->etat($user, Departement::create(['designation' => 'Achats']), 'EB-MULTI', 'En attente');
        Storage::disk('public')->put('ancienne.pdf', 'ancienne');
        $etat->update(['piece_justificative' => 'ancienne.pdf', 'piece_justificative_nom' => 'Ancienne facture.pdf']);
        $this->actingAs($user)->post(route('etat-besoins.piece-justificative.store', $etat), [
            'pieces_justificatives' => [
                UploadedFile::fake()->create('facture.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('recu.pdf', 100, 'application/pdf'),
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('ancienne.pdf', $etat->fresh()->piece_justificative);
        Storage::disk('public')->assertExists('ancienne.pdf');
        $this->assertCount(2, $etat->fresh()->pieces_justificatives);
        $this->get(route('etat-besoins.show', $etat))->assertOk()->assertSee('Ancienne facture.pdf')->assertSee('facture.pdf')->assertSee('recu.pdf');
        foreach ($etat->fresh()->pieces_justificatives as $piece) {
            $this->get(route('etat-besoins.piece-justificative.show', ['id' => $etat->id, 'piece' => hash('sha256', $piece['path']), 'telecharger' => 1]))->assertOk()->assertDownload($piece['nom']);
        }
        $this->get(route('etat-besoins.piece-justificative.show', ['id' => $etat->id, 'piece' => 'inconnue']))->assertNotFound();
        $this->post(route('etat-besoins.piece-justificative.store', $etat), [
            'pieces_justificatives' => [
                UploadedFile::fake()->create('valide.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('script.php', 1, 'text/plain'),
            ],
        ])->assertSessionHasErrors('pieces_justificatives.1');
        $this->assertCount(2, $etat->fresh()->pieces_justificatives);
    }

    public function test_une_piece_justificative_peut_etre_ajoutee_et_consultee(): void
    {
        Storage::fake('public');
        $role = Role::firstOrCreate(['designation' => 'Super Admin']);
        $user = $this->user($role, 50);
        $departement = Departement::create(['designation' => 'Achats']);
        $etat = $this->etat($user, $departement, 'EB-PIECE', 'En attente');

        $this->actingAs($user)->get(route('etat-besoins.show', $etat))
            ->assertOk()
            ->assertSee('Ajouter une pièce')
            ->assertSee('name="pieces_justificatives[]"', false);

        $this->actingAs($user)->post(route('etat-besoins.piece-justificative.store', $etat), [
            'piece_justificative' => UploadedFile::fake()->create('facture.pdf', 100, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('success');

        $etat->refresh();
        $this->assertSame('facture.pdf', $etat->piece_justificative_nom);
        Storage::disk('public')->assertExists($etat->piece_justificative);

        $sortie = $this->sortie($user, $etat, 'BSC-PIECE', 'En attente');
        $this->actingAs($user)->get(route('sortie-caisses.show', $sortie))
            ->assertOk()
            ->assertSee('Pièce justificative de l’état de besoin')
            ->assertSee('facture.pdf');

        $this->actingAs($user)->get(route('etat-besoins.show', $etat))
            ->assertOk()
            ->assertSee('facture.pdf')
            ->assertSee('Consulter')
            ->assertSee('Télécharger');
        $this->actingAs($user)->get(route('etat-besoins.piece-justificative.show', $etat))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="facture.pdf"');
    }

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

    public function test_un_etat_valide_sans_piece_est_verrouille_sauf_pour_ajouter_la_piece(): void
    {
        $role = Role::create(['designation' => 'Super Admin']);
        $user = $this->user($role, 24);
        $departement = Departement::create(['designation' => 'Administration']);
        $etat = $this->etat($user, $departement, 'EB-VERROUILLE', 'Validé');

        $this->actingAs($user)->get(route('etat-besoins.show', $etat))
            ->assertOk()
            ->assertSee('Document verrouillé après validation')
            ->assertSee('name="pieces_justificatives[]"', false)
            ->assertDontSee(route('etat-besoins.edit', $etat), false)
            ->assertSee('id="modalSuppressionDocument"', false)
            ->assertDontSee(route('etat-besoins.valider', $etat), false);

        $this->actingAs($user)->get(route('etat-besoins.edit', $etat))->assertForbidden();
        $this->actingAs($user)->put(route('etat-besoins.update', $etat), [])->assertForbidden();
        $this->actingAs($user)->post(route('etat-besoins.valider', $etat), [
            'observation' => 'Nouvelle validation', 'action' => 'valider', 'monnaie' => 'CDF',
        ])->assertForbidden();
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
