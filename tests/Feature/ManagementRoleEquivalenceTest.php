<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Departement;
use App\Models\EtatBesoin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ManagementRoleEquivalenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_charge_technique_est_redirige_vers_les_etats_de_besoin_apres_connexion(): void
    {
        foreach (['Chargé technique', 'Charge Technique', 'Chargé Technique', 'Charger Technique'] as $index => $designation) {
            $role = Role::firstOrCreate(['designation' => $designation]);
            $user = User::create([
                'nom' => 'Test', 'prenom' => $designation,
                'email' => 'connexion-technique'.$index.'@test.local',
                'password' => bcrypt('password'), 'role_id' => $role->id,
                'password_default' => false, 'statut' => 'Actif',
            ]);

            $this->post(route('handlelogin'), [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect(route('etat-besoins.index'));

            $this->post(route('logout'));
        }
    }

    public function test_admin_director_and_manager_roles_are_functionally_equivalent(): void
    {
        $designations = ['Admin', 'Directeur Général', 'Gérant', 'Gerant'];

        foreach ($designations as $index => $designation) {
            $role = Role::firstOrCreate(['designation' => $designation]);
            $user = User::create([
                'nom' => 'Test', 'prenom' => $designation,
                'email' => 'equivalent'.$index.'@test.local',
                'password' => bcrypt('password'), 'role_id' => $role->id,
                'password_default' => false, 'statut' => 'Actif',
            ]);

            foreach ($designations as $equivalent) {
                $this->assertTrue($user->hasRole($equivalent));
            }
            $this->assertTrue($user->isManagement());

            $this->actingAs($user)->get(route('profil.create'))->assertOk();
            $this->actingAs($user)->get(route('parametres.entreprise'))->assertOk();
            $this->actingAs($user)->get(route('parametres.departements'))->assertOk();
            $this->actingAs($user)->get(route('parametres.utilisateurs'))->assertOk();
            $this->actingAs($user)->get(route('parametres.comptables.index'))->assertForbidden();

            $this->assertTrue(Gate::forUser($user)->allows('manageUsers'));
            $this->assertTrue(Gate::forUser($user)->allows('viewAccountingReports'));
            $this->assertTrue(Gate::forUser($user)->allows('manageAccountingConfiguration'));

            foreach (['dashboard', 'etat-besoins.index', 'entree-caisses.index', 'sortie-caisses.index', 'ecritures.liste'] as $route) {
                $this->actingAs($user)->get(route($route))->assertOk()->assertDontSee('Validé par');
            }

            $this->actingAs($user)->get(route('etat-besoins.create'))->assertForbidden();
            $this->actingAs($user)->get(route('etat-besoins.index'))
                ->assertOk()
                ->assertDontSee(route('etat-besoins.create'));
            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee('Nouvel état de besoin');

            $this->actingAs($user)->get(route('entree-caisses.index'))
                ->assertOk()
                ->assertSee('Liste des Entrées')
                ->assertDontSee('Nouvelle Entrée')
                ->assertDontSee(route('entree-caisses.create'))
                ->assertDontSee(route('entree-caisses.statistiques'));
            $this->actingAs($user)->get(route('entree-caisses.create'))->assertForbidden();
            $this->actingAs($user)->get(route('entree-caisses.statistiques'))->assertForbidden();
            $this->actingAs($user)->get(route('dashboard'))->assertDontSee('Nouvelle entrée');

            $this->actingAs($user)->get(route('journaux.releve'))->assertOk();
            $this->actingAs($user)->get(route('journaux.tresorerie'))->assertOk();
            foreach (['journaux.index', 'journaux.create', 'journaux.create.caisse', 'journaux.banque', 'journaux.mobile'] as $route) {
                $this->actingAs($user)->get(route($route))->assertForbidden();
            }
            $this->actingAs($user)->get(route('dashboard'))
                ->assertSee(route('journaux.releve'), false)
                ->assertSee(route('journaux.tresorerie'), false)
                ->assertDontSee('Nouveau journal')
                ->assertDontSee('Journal Caisse')
                ->assertDontSee('Journal Banque')
                ->assertDontSee('Journal Mobile Money')
                ->assertDontSee('Journal des opérations diverses');

            $this->actingAs($user)->get(route('parametres.parametre'))
                ->assertOk()
                ->assertSee('Identité, coordonnées et informations légales.')
                ->assertSee('Départements et fonctions')
                ->assertSee('Créer les comptes et consulter les dernières connexions.')
                ->assertDontSee('Plan comptable')
                ->assertDontSee('Types de journaux')
                ->assertDontSee('Taux de change')
                ->assertDontSee('Paramétrage comptable');

            foreach (['parametres.comptes', 'parametres.journal-types', 'parametres.taux-change.create', 'parametres.comptables.index'] as $route) {
                $this->actingAs($user)->get(route($route))->assertForbidden();
            }
        }
    }

    public function test_super_admin_sees_validator_columns_everywhere(): void
    {
        $role = Role::create(['designation' => 'Super Admin']);
        $user = User::create([
            'nom' => 'Test', 'prenom' => 'Super Admin',
            'email' => 'superadmin@test.local', 'password' => bcrypt('password'),
            'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif',
        ]);

        foreach (['dashboard', 'etat-besoins.index', 'entree-caisses.index', 'sortie-caisses.index', 'journaux.index', 'ecritures.liste'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()->assertSee('Validé par');
        }

        $this->actingAs($user)->get(route('etat-besoins.create'))->assertOk();
        $this->actingAs($user)->get(route('parametres.parametre'))
            ->assertOk()
            ->assertSee('Entreprise')
            ->assertSee('Départements et fonctions')
            ->assertSee('Utilisateurs');
    }

    public function test_comptable_accede_aux_parametres_comptables(): void
    {
        $role = Role::create(['designation' => 'Comptable']);
        $user = User::create([
            'nom' => 'Test', 'prenom' => 'Comptable', 'email' => 'comptable-parametres@test.local',
            'password' => bcrypt('password'), 'role_id' => $role->id,
            'password_default' => false, 'statut' => 'Actif',
        ]);

        $this->actingAs($user)->get(route('parametres.parametre'))
            ->assertOk()
            ->assertSee('Plan comptable')
            ->assertSee('Types de journaux')
            ->assertSee('Taux de change')
            ->assertSee('Paramétrage comptable')
            ->assertDontSee('Identité, coordonnées et informations légales.')
            ->assertDontSee('Départements et fonctions')
            ->assertDontSee('Créer les comptes et consulter les dernières connexions.');
    }

    public function test_daf_et_comptable_sont_fonctionnellement_equivalents(): void
    {
        foreach (['DAF', 'Comptable', 'Chargé des finances', 'Chargé de finance', 'Charger de finance'] as $index => $designation) {
            $role = Role::firstOrCreate(['designation' => $designation]);
            $user = User::create([
                'nom' => 'Test', 'prenom' => $designation,
                'email' => 'comptabilite'.$index.'@test.local', 'password' => bcrypt('password'),
                'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif',
            ]);

            $this->assertTrue($user->hasRole('DAF'));
            $this->assertTrue($user->hasRole('Comptable'));
            $this->assertTrue($user->isAccounting());
            $this->assertTrue(Gate::forUser($user)->allows('viewAccountingReports'));
            $this->assertTrue(Gate::forUser($user)->allows('manageAccountingConfiguration'));
            $this->assertTrue(Gate::forUser($user)->allows('validateAccountingEntries'));

            foreach (['parametres.parametre', 'parametres.comptes', 'parametres.journal-types', 'parametres.taux-change.create', 'parametres.comptables.index', 'ecritures.create'] as $route) {
                $this->actingAs($user)->get(route($route))->assertOk();
            }

            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Statistiques')
                ->assertSee(route('dashboard'), false)
                ->assertSee('Nouvelle écriture');
        }
    }

    public function test_directeur_technique_a_uniquement_son_perimetre_autorise(): void
    {
        foreach (['Directeur Technique', 'Chargé technique', 'Chargé Technique', 'Charger Technique'] as $index => $designation) {
        $role = Role::firstOrCreate(['designation' => $designation]);
        $user = User::create([
            'nom' => 'Test', 'prenom' => 'Direction technique',
            'email' => 'direction-technique'.$index.'@test.local', 'password' => bcrypt('password'),
            'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif',
        ]);

        foreach (['etat-besoins.index', 'etat-besoins.create', 'journaux.index', 'profil.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('etat-besoins.index'));

        foreach (['entree-caisses.index', 'sortie-caisses.index', 'journaux.create', 'journaux.releve', 'journaux.tresorerie', 'ecritures.liste'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }

        $this->actingAs($user)->get(route('parametres.parametre'))
            ->assertOk()->assertSee('Cartes de service')->assertDontSee('Entreprise');
        $this->actingAs($user)->get(route('parametres.cartes-service.index'))->assertOk();

        $this->actingAs($user)->get(route('journaux.index'))
            ->assertOk()
            ->assertDontSee('Actions')
            ->assertDontSee('Journal Caisse')
            ->assertDontSee('Journal Banque')
            ->assertDontSee('Journal Mobile Money');

        $this->actingAs($user)->get(route('etat-besoins.index'))
            ->assertOk()
            ->assertSee(route('etat-besoins.create'), false)
            ->assertSee(route('journaux.index'), false)
            ->assertDontSee(route('dashboard'), false)
            ->assertSee(route('parametres.parametre'), false);
        }
    }

    public function test_chefs_voient_uniquement_la_liste_et_la_creation_des_etats_de_besoin(): void
    {
        foreach (['Chef de Département', 'Chef de Service'] as $index => $designation) {
            $role = Role::create(['designation' => $designation]);
            $departement = Departement::create(['designation' => 'Département '.$index]);
            $user = User::create([
                'nom' => 'Test', 'prenom' => $designation,
                'email' => 'chef'.$index.'@test.local', 'password' => bcrypt('password'),
                'role_id' => $role->id, 'departement_id' => $departement->id,
                'password_default' => false, 'statut' => 'Actif',
            ]);
            $etat = EtatBesoin::create([
                'user_id' => $user->id, 'departement_id' => $departement->id,
                'numero' => 'EB-CHEF-'.$index, 'date' => now(), 'service' => $departement->designation,
                'demandeur' => $user->prenom, 'motif' => 'Test', 'montant_estime' => 100,
                'monnaie' => 'CDF', 'statut' => 'En attente',
            ]);

            $this->actingAs($user)->get(route('etat-besoins.index'))
                ->assertOk()
                ->assertSee(route('etat-besoins.create'), false)
                ->assertDontSee('Actions');
            $this->actingAs($user)->get(route('etat-besoins.create'))->assertOk();
            $this->actingAs($user)->get(route('profil.index'))->assertOk();

            $this->actingAs($user)->get(route('etat-besoins.show', $etat))->assertForbidden();
            foreach (['dashboard', 'parametres.parametre', 'entree-caisses.index', 'sortie-caisses.index', 'journaux.index', 'journaux.tresorerie', 'ecritures.liste'] as $route) {
                $this->actingAs($user)->get(route($route))->assertForbidden();
            }
        }
    }

    public function test_caissiers_et_tresoriers_ont_le_bon_perimetre_des_journaux(): void
    {
        foreach (['Caissier', 'Caissière', 'Trésorier', 'Trésorière'] as $index => $designation) {
            $role = Role::create(['designation' => $designation]);
            $user = User::create([
                'nom' => 'Test', 'prenom' => $designation,
                'email' => 'tresorerie'.$index.'@test.local', 'password' => bcrypt('password'),
                'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif',
            ]);

            foreach (['profil.index', 'journaux.index', 'journaux.banque', 'journaux.mobile', 'journaux.releve', 'journaux.create', 'journaux.create.caisse', 'journaux.create.banque', 'journaux.create.mobile', 'ecritures.create'] as $route) {
                $this->actingAs($user)->get(route($route))->assertOk();
            }

            if (in_array($designation, ['Caissier', 'Caissière'], true)) {
                $this->actingAs($user)->get(route('journaux.tresorerie'))->assertForbidden();
                $this->actingAs($user)->get(route('journaux.releve'))
                    ->assertOk()
                    ->assertDontSee(route('journaux.tresorerie'), false);
            } else {
                $this->actingAs($user)->get(route('journaux.tresorerie'))->assertOk();
                $this->actingAs($user)->get(route('journaux.releve'))
                    ->assertOk()
                    ->assertSee(route('journaux.tresorerie'), false);
            }
        }
    }
}
