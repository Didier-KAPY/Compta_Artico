<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class BudgetImportSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_enregistre_un_budget(): void
    {
        $user = $this->admin();
        $compte = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '601100', 'designation' => 'Achats', 'nature' => 'Charge']);
        $this->actingAs($user)->post(route('parametres.budgets.store'), [
            'liste_des_comptes_id' => $compte->id, 'date_debut' => '2026-01-01', 'date_fin' => '2026-12-31',
            'montant_prevu' => 150000, 'monnaie' => 'CDF',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('150000.00', Budget::firstOrFail()->montant_prevu);
    }

    public function test_import_csv_est_previsualise_avant_confirmation(): void
    {
        $user = $this->admin();
        $file = UploadedFile::fake()->createWithContent('comptes.csv', "compte;designation;nature\n701100;Ventes;Produit\n");
        $this->actingAs($user)->post(route('parametres.imports.preview'), ['fichier' => $file])
            ->assertRedirect(route('parametres.imports.index'));
        $this->assertDatabaseMissing('liste_des_comptes', ['compte' => '701100']);
        $this->actingAs($user)->post(route('parametres.imports.store'))->assertRedirect();
        $this->assertDatabaseHas('liste_des_comptes', ['compte' => '701100', 'designation' => 'Ventes']);
    }

    public function test_sauvegardes_sont_reservees_au_super_admin(): void
    {
        $user = $this->admin('Comptable');
        $this->actingAs($user)->get(route('parametres.sauvegardes.index'))->assertForbidden();
    }

    public function test_ecrans_administratifs_sont_rendus_et_connexion_est_limitee(): void
    {
        $user = $this->admin();
        $this->actingAs($user)->get(route('parametres.budgets.index'))->assertOk()->assertSee('Gestion budgétaire');
        $this->actingAs($user)->get(route('parametres.imports.index'))->assertOk()->assertSee('Import du plan comptable');
        $this->actingAs($user)->get(route('parametres.periodes.index'))->assertOk()->assertSee('Clôtures mensuelles et annuelles');
        $this->actingAs($user)->get(route('parametres.sauvegardes.index'))->assertOk()->assertSee('Sauvegardes MySQL');

        auth()->logout();
        RateLimiter::clear('inconnu@test.local|127.0.0.1');
        foreach (range(1, 5) as $attempt) {
            $this->post(route('handlelogin'), ['email' => 'inconnu@test.local', 'password' => 'incorrect']);
        }
        $this->post(route('handlelogin'), ['email' => 'inconnu@test.local', 'password' => 'incorrect'])
            ->assertSessionHasErrors('email');
    }

    private function admin(string $role = 'Super Admin'): User
    {
        $roleModel = Role::create(['designation' => $role]);
        return User::create(['nom' => 'Admin', 'prenom' => 'Test', 'email' => uniqid().'@test.local', 'password' => bcrypt('password'), 'role_id' => $roleModel->id, 'password_default' => 0, 'statut' => 'Actif']);
    }
}
