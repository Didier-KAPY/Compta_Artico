<?php

namespace Tests\Feature;

use App\Models\EtatBesoin;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_displays_pending_and_validated_notifications(): void
    {
        $admin = $this->userWithRole('Admin', 'admin-notifications@test.local');
        $creator = $this->userWithRole('Chef de Service', 'creator-notifications@test.local');

        $this->etat($creator, 'EB-ATTENTE-001', 'En attente');
        $this->etat($creator, 'EB-VALIDE-001', 'Validé');

        $this->actingAs($admin)
            ->get(route('profil.index'))
            ->assertOk()
            ->assertSee('topbar-notification-btn', false)
            ->assertSee('Notifications')
            ->assertDontSee('Notifications de suivi')
            ->assertSee('1 en attente')
            ->assertSee('1 validé')
            ->assertSee('EB-ATTENTE-001')
            ->assertSee('EB-VALIDE-001');
    }

    public function test_standard_user_only_sees_own_profile_notifications(): void
    {
        $chef = $this->userWithRole('Chef de Service', 'chef-notifications@test.local');
        $autre = $this->userWithRole('Chef de Département', 'autre-notifications@test.local');

        $this->etat($chef, 'EB-PERSONNEL-001', 'En attente');
        $this->etat($autre, 'EB-AUTRE-001', 'Validé');

        $this->actingAs($chef)
            ->get(route('profil.index'))
            ->assertOk()
            ->assertSee('EB-PERSONNEL-001')
            ->assertDontSee('EB-AUTRE-001');
    }

    private function userWithRole(string $designation, string $email): User
    {
        $role = Role::firstOrCreate(['designation' => $designation]);

        return User::create([
            'nom' => 'Test',
            'prenom' => $designation,
            'email' => $email,
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'password_default' => false,
            'statut' => 'Actif',
        ]);
    }

    private function etat(User $user, string $numero, string $statut): EtatBesoin
    {
        return EtatBesoin::create([
            'user_id' => $user->id,
            'numero' => $numero,
            'date' => now(),
            'service' => 'Service test',
            'demandeur' => $user->prenom,
            'motif' => 'Achat de fournitures',
            'montant_estime' => 100,
            'monnaie' => 'CDF',
            'statut' => $statut,
        ]);
    }
}