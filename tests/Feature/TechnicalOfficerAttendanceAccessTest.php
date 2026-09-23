<?php

namespace Tests\Feature;

use App\Models\{Entreprise, Role, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicalOfficerAttendanceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_technical_officer_sees_only_attendance_and_clocking_in_hr(): void
    {
        $role=Role::firstOrCreate(['designation'=>'Chargé technique']);
        $user=User::create(['nom'=>'Technique','prenom'=>'Agent','email'=>'tech-rh@test.local','password'=>bcrypt('password'),'role_id'=>$role->id,'password_default'=>0,'statut'=>'Actif']);
        Entreprise::create(['user_id'=>$user->id,'nom_entreprise'=>'Entreprise Test']);
        $this->actingAs($user);

        $this->get(route('parametres.parametre'))->assertOk()
            ->assertSee('Gestion des ressources humaines')
            ->assertSee('Présences, pointages et cartes de service.')
            ->assertSee(route('parametres.rh.presences'),false);

        $this->get(route('parametres.rh.presences'))->assertOk()
            ->assertSee(route('parametres.rh.presences'),false)
            ->assertSee(route('parametres.rh.presences.scanner'),false)
            ->assertSee(route('parametres.cartes-service.index'),false)
            ->assertDontSee(route('parametres.rh.employes'),false)
            ->assertDontSee(route('parametres.rh.contrats'),false)
            ->assertDontSee(route('parametres.rh.paie'),false)
            ->assertDontSee(route('parametres.rh.conges'),false)
            ->assertDontSee(route('parametres.rh.syntheses'),false);

        $this->get(route('parametres.rh.presences.scanner'))->assertOk();
        $this->get(route('parametres.cartes-service.index'))->assertOk();
        $this->get(route('parametres.rh.index'))->assertForbidden();
        $this->get(route('parametres.rh.employes'))->assertForbidden();
        $this->get(route('parametres.rh.contrats'))->assertForbidden();
        $this->get(route('parametres.rh.paie'))->assertForbidden();
        $this->get(route('parametres.rh.syntheses'))->assertForbidden();
    }
}
