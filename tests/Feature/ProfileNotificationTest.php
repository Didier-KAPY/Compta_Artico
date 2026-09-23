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

    public function test_profile_images_are_served_without_a_public_storage_link(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1sAAAAASUVORK5CYII=');
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $disk->put('photos/profile.png', $image);
        $disk->put('logos/company.png', $image);
        $user = $this->userWithRole('Admin', 'images@test.local');
        $user->update(['photo' => 'photos/profile.png']);
        \App\Models\Entreprise::create([
            'user_id' => $user->id,
            'nom_entreprise' => 'Test',
            'logo' => 'logos/company.png',
        ]);

        $this->get('/profil/photo')->assertRedirect(route('login'));
        $this->get('/profil/logo')->assertRedirect(route('login'));
        $this->actingAs($user);
        foreach (['/profil/photo', '/profil/logo'] as $url) {
            $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertContent($image);
        }
        $this->get(route('profil.index'))->assertOk()
            ->assertSee('src="/profil/photo"', false)
            ->assertSee('src="/profil/logo"', false);

        $disk->delete(['photos/profile.png', 'logos/company.png']);
        $this->get('/profil/photo')->assertNotFound();
        $this->get('/profil/logo')->assertNotFound();
    }

    public function test_profile_saves_postnom(): void
    {
        $user = $this->userWithRole('Admin', 'postnom@test.local');
        $this->actingAs($user)->post(route('profil.update'), [
            'nom' => $user->nom, 'prenom' => $user->prenom,
            'postnom' => 'Mwamba', 'email' => $user->email,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Mwamba', $user->fresh()->postnom);
        $this->get(route('profil.index'))->assertOk()->assertSee('Mwamba')->assertSee('Post nom');
    }

    public function test_profile_displays_only_pending_notifications(): void
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
            ->assertSee('EB-ATTENTE-001')
            ->assertDontSee('EB-VALIDE-001');
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
