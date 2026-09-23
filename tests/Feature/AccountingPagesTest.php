<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_pages_render_with_linux_compatible_view_names(): void
    {
        $role = Role::create(['designation' => 'Super Admin']);
        $user = User::create([
            'nom' => 'Admin',
            'prenom' => 'Comptabilite',
            'email' => 'accounting-pages@test.local',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'password_default' => 0,
            'statut' => 'Actif',
        ]);

        $this->actingAs($user);

        foreach ([
            '/ecritures' => 'Comptabilite.ecritures.liste',
            '/grand-livre' => 'Comptabilite.grandlivre.index',
            '/balance' => 'Comptabilite.balance.index',
        ] as $url => $view) {
            // Assert the exact case even when the tests run on Windows.
            $this->get($url)->assertOk()->assertViewIs($view);
        }
    }
}
