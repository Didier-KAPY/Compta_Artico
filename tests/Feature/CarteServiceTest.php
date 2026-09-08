<?php

namespace Tests\Feature;

use App\Models\CarteService;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\Employe;
use App\Models\Fonction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarteServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_cinq_roles_autorises_accedent_au_module(): void
    {
        foreach (['Super Admin', 'Admin', 'Gérant', 'Directeur Général', 'Directeur Technique', 'Chargé Technique'] as $index => $designation) {
            $user = $this->userWithRole($designation, $index);

            $this->actingAs($user)->get(route('parametres.parametre'))
                ->assertOk()->assertSee('Cartes de service');
            $this->actingAs($user)->get(route('parametres.cartes-service.index'))->assertOk();
            $this->actingAs($user)->get(route('parametres.cartes-service.create'))->assertOk();
        }
    }

    public function test_un_role_non_autorise_ne_peut_pas_acceder_aux_cartes(): void
    {
        $user = $this->userWithRole('Comptable', 20);

        $this->actingAs($user)->get(route('parametres.parametre'))
            ->assertOk()->assertDontSee('Cartes de service');
        $this->actingAs($user)->get(route('parametres.cartes-service.index'))->assertForbidden();
    }

    public function test_creation_et_affichage_utilisent_les_donnees_de_l_agent(): void
    {
        Storage::fake('public');
        $photo = imagecreatetruecolor(120, 160);
        imagefill($photo, 0, 0, imagecolorallocate($photo, 36, 105, 165));
        ob_start();
        imagejpeg($photo, null, 90);
        Storage::disk('public')->put('profils/agent-test.jpg', ob_get_clean());
        imagedestroy($photo);
        $admin = $this->userWithRole('Super Admin', 30);
        $departement = Departement::create(['designation' => 'Direction technique']);
        $fonction = Fonction::create(['designation' => 'Ingénieur principal']);
        $agent = $this->userWithRole('Agent', 31, [
            'nom' => 'KAPY', 'prenom' => 'Didier', 'adresse' => '12, avenue du Fleuve',
            'photo' => 'profils/agent-test.jpg',
            'departement_id' => $departement->id, 'fonction_id' => $fonction->id,
        ]);
        $entreprise = $admin->entreprises()->create([
            'nom_entreprise' => 'ARTICO SARL',
            'adresse' => 'Kinshasa', 'telephone' => '0990000000',
        ]);
        Employe::create([
            'entreprise_id' => $entreprise->id, 'user_id' => $agent->id,
            'matricule' => 'DG-2026-00001', 'nom' => 'KAPY', 'prenom' => 'Didier', 'statut' => 'Actif',
        ]);

        $response = $this->actingAs($admin)->post(route('parametres.cartes-service.store'), [
            'user_id' => $agent->id,
            'postnom' => 'MUKENDI',
            'adresse' => '',
            'date_naissance' => '1990-06-15',
            'sexe' => 'Masculin',
            'date_delivrance' => '2026-07-30',
            'nom_signataire' => 'Jean Gérant',
        ]);

        $carte = CarteService::firstOrFail();
        $response->assertRedirect(route('parametres.cartes-service.show', $carte));
        $this->assertSame('CS-2026-00001', $carte->numero);
        $this->assertDatabaseHas('cartes_service', [
            'user_id' => $agent->id, 'postnom' => 'MUKENDI', 'nom_signataire' => 'Jean Gérant',
        ]);

        $this->actingAs($admin)->get(route('parametres.cartes-service.show', $carte))
            ->assertOk()
            ->assertSee('font-size:1.85mm; font-weight:600; line-height:2.4mm;', false)
            ->assertSee('ARTICO SARL')
            ->assertSee('KAPY MUKENDI Didier')
            ->assertSee('Direction technique')
            ->assertSee('Ingénieur principal')
            ->assertSee('N° carte :')
            ->assertSee('CS-2026-00001')
            ->assertSee('N° matricule :</b> DG-00001', false)
            ->assertDontSee('N° matricule :</b> DG-2026-00001', false)
            ->assertSee('12, avenue du Fleuve')
            ->assertSee('Les autorités tant civiles que militaires ou policières sont priées d’apporter leur assistance au porteur de la présente.')
            ->assertSee('data:image/png;base64', false)
            ->assertSee('Code QR de pointage arrivée et départ')
            ->assertSee('service-card sc-front', false)
            ->assertSee('class="sc-photo" src="/parametres/cartes-service/'.$carte->id.'/photo?', false)
            ->assertSee('service-card sc-back', false)
            ->assertSee('Pointage du personnel')
            ->assertSee("pdf.addPage([53.98,85.60],'portrait')", false)
            ->assertSee('PDF identique à l’aperçu')
            ->assertSee('download-card-pdf', false)
            ->assertSee('download-card-jpeg-front', false)
            ->assertSee('download-card-jpeg-back', false)
            ->assertSee('Télécharger le recto')
            ->assertSee('Télécharger le verso')
            ->assertSee("toDataURL('image/jpeg',.96)", false)
            ->assertSee("img.decode()", false)
            ->assertSee("imageTimeout:15000", false)
            ->assertSee("result.getContext('2d').drawImage(photo", false)
            ->assertSee("get('telecharger')==='pdf'", false)
            ->assertSee('Jean Gérant');

        $this->actingAs($admin)->get(route('parametres.cartes-service.index'))
            ->assertOk()
            ->assertSee(route('parametres.cartes-service.pdf', $carte), false)
            ->assertSee('Télécharger le PDF avec la photo');

        $employe = Employe::where('user_id', $agent->id)->firstOrFail()->refresh();
        $this->assertNotNull($employe->qr_token);
        $this->actingAs($admin)->postJson(route('parametres.rh.presences.scan'), [
            'code' => 'rh-attendance:'.$employe->qr_token,
        ])->assertOk()->assertJsonPath('operation', 'ARRIVÉE');

        $pdf = $this->actingAs($admin)->get(route('parametres.cartes-service.pdf', $carte));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertGreaterThanOrEqual(2, substr_count($pdf->getContent(), '/Subtype /Image'));
        $this->actingAs($admin)->get(route('parametres.cartes-service.photo', $carte))
            ->assertOk()->assertHeader('content-type', 'image/jpeg');
    }

    private function userWithRole(string $designation, int $index, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['designation' => $designation]);

        return User::create(array_merge([
            'nom' => 'Test', 'prenom' => $designation,
            'email' => 'carte'.$index.'@test.local', 'password' => bcrypt('password'),
            'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif',
        ], $attributes));
    }
}
