<?php

namespace Tests\Feature;

use App\Models\PeriodeComptable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodeComptableTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_ferme_et_reouvre_une_periode(): void
    {
        $user = $this->admin();
        $this->actingAs($user)->post(route('parametres.periodes.store'), ['type' => 'mensuelle', 'periode' => '2026-08'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $periode = PeriodeComptable::firstOrFail();
        $this->assertSame('2026-08-01', $periode->date_debut->toDateString());
        $this->assertSame('2026-08-31', $periode->date_fin->toDateString());

        $this->actingAs($user)->post(route('parametres.periodes.reouvrir', $periode), ['motif' => 'Correction comptable autorisée.'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('reouverte', $periode->fresh()->statut);
    }

    public function test_une_operation_datee_est_bloquee_dans_une_periode_fermee(): void
    {
        $user = $this->admin();
        PeriodeComptable::create(['type' => 'mensuelle', 'date_debut' => '2026-08-01', 'date_fin' => '2026-08-31', 'statut' => 'fermee']);

        $this->actingAs($user)->post(route('ecritures.store'), ['date' => '2026-08-08'])
            ->assertSessionHasErrors('date');
    }

    public function test_super_admin_peut_imprimer_et_telecharger_les_periodes(): void
    {
        $user = $this->admin();
        PeriodeComptable::create(['type' => 'mensuelle', 'date_debut' => '2026-08-01', 'date_fin' => '2026-08-31', 'statut' => 'fermee']);

        $this->actingAs($user)->get(route('parametres.periodes.imprimer'))
            ->assertOk()
            ->assertSee('Historique des périodes comptables')
            ->assertSee('01/08/2026');

        $this->actingAs($user)->get(route('parametres.periodes.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function admin(): User
    {
        $role = Role::create(['designation' => 'Super Admin']);
        return User::create(['nom' => 'Admin', 'prenom' => 'Période', 'email' => uniqid().'@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => 0, 'statut' => 'Actif']);
    }
}
