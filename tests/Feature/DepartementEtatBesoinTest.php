<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EtatBesoin;
use App\Models\Entreprise;
use App\Models\Fonction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartementEtatBesoinTest extends TestCase
{
    use RefreshDatabase;

    public function test_utilisateur_ne_voit_que_les_etats_de_son_departement(): void
    {
        [$userA, $userB, $admin, $departementA, $departementB] = $this->contexte();
        $etatA = $this->etat($userA, $departementA, 'EB-DEPT-A');
        $etatB = $this->etat($userB, $departementB, 'EB-DEPT-B');
        Entreprise::forceCreate([
            'user_id' => $admin->id,
            'nom_entreprise' => 'Entreprise test',
            'slogan' => 'Le slogan configuré',
        ]);

        $this->actingAs($userA)->get(route('etat-besoins.index'))
            ->assertOk()->assertSee('EB-DEPT-A')->assertDontSee('EB-DEPT-B');
        $this->actingAs($userA)->get(route('etat-besoins.show', $etatB))->assertForbidden();
        $this->actingAs($userA)->get(route('etat-besoins.show', $etatA))->assertForbidden();
        $this->actingAs($userA)->get(route('etat-besoins.imprimer', $etatA))->assertForbidden();
        $this->actingAs($userA)->get(route('etat-besoins.pdf', $etatA))->assertForbidden();
        $this->actingAs($userA)->get(route('etat-besoins.pdf', $etatB))->assertForbidden();

        $this->actingAs($admin)->get(route('etat-besoins.index'))
            ->assertOk()->assertSee('EB-DEPT-A')->assertSee('EB-DEPT-B');
    }

    public function test_creation_affiche_tous_les_departements_et_enregistre_la_relation(): void
    {
        [$userA, , , $departementA, $departementB] = $this->contexte();

        $this->actingAs($userA)->get(route('etat-besoins.create'))
            ->assertOk()->assertSee($departementA->designation)->assertSee($departementB->designation);

        $this->actingAs($userA)->post(route('etat-besoins.store'), [
            'date' => now()->toDateString(), 'departement_id' => $departementB->id,
            'demandeur' => 'Agent test', 'motif' => 'Besoin test', 'monnaie' => 'CDF',
            'designation' => ['Fourniture'], 'quantite' => [2], 'prix_unitaire' => [50],
        ])->assertRedirect(route('etat-besoins.create'));

        $this->assertDatabaseHas('etat_besoins', [
            'user_id' => $userA->id, 'departement_id' => $departementB->id,
            'service' => $departementB->designation, 'montant_estime' => 100,
        ]);
    }

    public function test_parametres_permettent_de_creer_et_affecter_un_departement(): void
    {
        [$user, , $admin, $departement] = $this->contexte();

        $this->actingAs($admin)->get(route('parametres.departements'))->assertOk()->assertSee('Départements');
        $this->actingAs($admin)->post(route('parametres.departements.store'), [
            'designation' => 'Logistique',
        ])->assertRedirect();
        $logistique = Departement::where('designation', 'Logistique')->firstOrFail();
        $this->actingAs($admin)->post(route('parametres.fonctions.store'), [
            'designation' => 'Approvisionnement',
        ])->assertRedirect();
        $fonction = Fonction::where('designation', 'Approvisionnement')->firstOrFail();

        $this->actingAs($admin)->patch(route('parametres.utilisateurs.departement', $user), [
            'departement_id' => $logistique->id,
            'fonction_id' => $fonction->id,
        ])->assertRedirect();
        $this->assertSame($logistique->id, $user->fresh()->departement_id);
        $this->assertSame($fonction->id, $user->fresh()->fonction_id);
    }

    public function test_creation_utilisateur_enregistre_departement_et_fonction(): void
    {
        [, , $admin, $departement] = $this->contexte();
        $fonction = Fonction::create(['designation' => 'Responsable logistique']);
        $role = Role::firstOrCreate(['designation' => 'Agent']);

        $this->actingAs($admin)->post(route('profil.user.store'), [
            'nom' => 'Nouveau', 'prenom' => 'Collaborateur',
            'email' => 'nouveau@departement.test', 'telephone' => '0990000000',
            'adresse' => 'Kinshasa', 'role_id' => $role->id,
            'departement_id' => $departement->id, 'fonction_id' => $fonction->id,
            'statut' => 'Actif',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'nouveau@departement.test',
            'departement_id' => $departement->id,
            'fonction_id' => $fonction->id,
        ]);
    }

    public function test_profil_affiche_affectations_et_ne_permet_pas_de_changer_son_role(): void
    {
        [$user, , , $departement] = $this->contexte();
        $fonction = Fonction::create(['designation' => 'Technicien']);
        $user->update(['fonction_id' => $fonction->id]);
        $autreRole = Role::firstOrCreate(['designation' => 'Super Admin']);

        $this->actingAs($user)->get(route('profil.index'))
            ->assertOk()->assertSee($departement->designation)->assertSee($fonction->designation);

        $this->actingAs($user)->post(route('profil.update'), [
            'nom' => 'Modifié', 'prenom' => $user->prenom,
            'email' => $user->email, 'telephone' => '0812345678',
            'adresse' => '', 'role_id' => $autreRole->id,
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('Modifié', $user->nom);
        $this->assertSame('0812345678', $user->telephone);
        $this->assertSame('', (string) $user->adresse);
        $this->assertNotSame($autreRole->id, $user->role_id);
    }

    private function contexte(): array
    {
        $roleAgent = Role::firstOrCreate(['designation' => 'Chef de Service']);
        $roleAdmin = Role::firstOrCreate(['designation' => 'Super Admin']);
        $departementA = Departement::create(['designation' => 'Technique']);
        $departementB = Departement::create(['designation' => 'Finance']);
        $userA = $this->user($roleAgent, $departementA, 'a');
        $userB = $this->user($roleAgent, $departementB, 'b');
        $admin = $this->user($roleAdmin, null, 'admin');
        return [$userA, $userB, $admin, $departementA, $departementB];
    }

    private function user(Role $role, ?Departement $departement, string $suffixe): User
    {
        return User::create(['nom' => 'Test', 'prenom' => $suffixe, 'email' => $suffixe.'@departement.test', 'password' => bcrypt('password'), 'role_id' => $role->id, 'departement_id' => $departement?->id, 'password_default' => 0, 'statut' => 'Actif']);
    }

    private function etat(User $user, Departement $departement, string $numero): EtatBesoin
    {
        return EtatBesoin::create(['user_id' => $user->id, 'departement_id' => $departement->id, 'numero' => $numero, 'date' => now()->toDateString(), 'service' => $departement->designation, 'demandeur' => $user->prenom, 'motif' => 'Test', 'monnaie' => 'CDF', 'statut' => 'En attente']);
    }
}
