<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Employe;
use App\Models\Entreprise;
use App\Models\Role;
use App\Models\User;
use App\Services\MatriculeEmployeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculeEmployeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_matricule_utilise_les_initiales_de_la_direction(): void
    {
        [$entreprise, $direction] = $this->contexte('Direction des Ressources Humaines');
        $service = app(MatriculeEmployeService::class);

        $this->assertSame('DRH-'.now()->format('Y').'-00001', $service->generer($entreprise->id, $direction->id));
        $this->assertSame('DG', $service->initiales('Direction Générale'));
    }

    public function test_sequence_est_independante_pour_chaque_direction(): void
    {
        [$entreprise, $technique] = $this->contexte('Direction Technique');
        $finance = Departement::create(['entreprise_id' => $entreprise->id, 'designation' => 'Direction Financière', 'actif' => true]);
        $service = app(MatriculeEmployeService::class);
        $annee = now()->format('Y');
        Employe::create(['entreprise_id' => $entreprise->id, 'departement_id' => $technique->id, 'matricule' => "DT-$annee-00001", 'nom' => 'Premier', 'statut' => 'Actif']);

        $this->assertSame("DT-$annee-00002", $service->generer($entreprise->id, $technique->id));
        $this->assertSame("DF-$annee-00001", $service->generer($entreprise->id, $finance->id));
    }

    private function contexte(string $designation): array
    {
        $role = Role::firstOrCreate(['designation' => 'Super Admin']);
        $user = User::create(['nom' => 'Admin', 'prenom' => 'RH', 'email' => uniqid().'@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'statut' => 'Actif']);
        $entreprise = Entreprise::create(['user_id' => $user->id, 'nom_entreprise' => 'ARTICO']);
        $direction = Departement::create(['entreprise_id' => $entreprise->id, 'designation' => $designation, 'actif' => true]);

        return [$entreprise, $direction];
    }
}
