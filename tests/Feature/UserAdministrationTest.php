<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_roles_can_change_a_user_role_and_reset_password(): void
    {
        foreach (['Super Admin', 'Admin', 'Gérant', 'Gerant', 'Directeur Général'] as $designation) {
            $role = Role::create(['designation' => $designation]);
            $actor = $this->user($role, str_replace(' ', '', $designation));
            $newRole = Role::firstOrCreate(['designation' => 'Comptable']);
            $target = $this->user($newRole, 'target'.md5($designation));
            $oldPassword = $target->password;

            $this->actingAs($actor)->patch(route('parametres.utilisateurs.role', $target), [
                'role_id' => $role->id,
            ])->assertRedirect();
            $this->assertSame($role->id, $target->fresh()->role_id);

            $response = $this->actingAs($actor)->patch(route('parametres.utilisateurs.password.reset', $target))
                ->assertRedirect()
                ->assertSessionHas('password_default');

            $target->refresh();
            $this->assertTrue($target->password_default);
            $this->assertNotSame($oldPassword, $target->password);
            $this->assertTrue(Hash::check($response->getSession()->get('password_default'), $target->password));
        }
    }

    public function test_only_super_admin_can_manage_a_super_admin_account_or_role(): void
    {
        $adminRole = Role::create(['designation' => 'Admin']);
        $superRole = Role::create(['designation' => 'Super Admin']);
        $admin = $this->user($adminRole, 'admin');
        $super = $this->user($superRole, 'super');
        $target = $this->user($adminRole, 'target');

        $this->actingAs($admin)->patch(route('parametres.utilisateurs.password.reset', $super))->assertForbidden();
        $this->actingAs($admin)->patch(route('parametres.utilisateurs.role', $target), ['role_id' => $superRole->id])->assertForbidden();
        $this->actingAs($admin)->patch(route('parametres.utilisateurs.role', $super), ['role_id' => $adminRole->id])->assertForbidden();
    }

    private function user(Role $role, string $suffix): User
    {
        return User::create([
            'nom' => 'Test', 'prenom' => 'Utilisateur',
            'email' => $suffix.'@users.test', 'telephone' => '000',
            'password' => Hash::make('ancien-mot-de-passe'), 'role_id' => $role->id,
            'password_default' => false, 'statut' => 'Actif',
        ]);
    }
}
