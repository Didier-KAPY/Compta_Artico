<?php

namespace Tests\Feature;

use App\Models\Employe;
use App\Models\Entreprise;
use App\Models\RhHoraire;
use App\Models\RhPresence;
use App\Models\Role;
use App\Models\User;
use App\Services\RhSyntheseMensuelleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class QrAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_premier_scan_enregistre_arrivee_et_second_scan_depart(): void
    {
        [$admin, $employe] = $this->contexte();
        Carbon::setTestNow('2026-09-07 08:17:00');

        $this->actingAs($admin)->postJson(route('parametres.rh.presences.scan'), ['code' => $this->code($employe)])
            ->assertOk()->assertJsonPath('operation', 'ARRIVÉE')->assertJsonPath('retard_minutes', 17);
        $this->assertDatabaseHas('rh_presences', ['employe_id' => $employe->id, 'heure_arrivee' => '08:17:00', 'methode_pointage' => 'QR Code', 'retard_minutes' => 17]);

        Carbon::setTestNow('2026-09-07 16:30:00');
        Cache::forget('rh-qr-scan:'.hash('sha256', $employe->qr_token));
        $this->actingAs($admin)->postJson(route('parametres.rh.presences.scan'), ['code' => $this->code($employe)])
            ->assertOk()->assertJsonPath('operation', 'DÉPART')->assertJsonPath('depart_anticipe_minutes', 30);
        $this->assertDatabaseHas('rh_presences', ['employe_id' => $employe->id, 'heure_depart' => '16:30:00', 'heures_travaillees' => 8.22, 'depart_anticipe_minutes' => 30, 'statut_validation' => 'Validé', 'valide_par' => $admin->id]);
    }

    public function test_scan_repete_est_bloque_et_troisieme_scan_est_refuse(): void
    {
        [$admin, $employe] = $this->contexte();
        Carbon::setTestNow('2026-09-07 08:00:00');
        $this->actingAs($admin)->postJson(route('parametres.rh.presences.scan'), ['code' => $this->code($employe)])->assertOk();
        $this->actingAs($admin)->postJson(route('parametres.rh.presences.scan'), ['code' => $this->code($employe)])->assertStatus(429);

        Cache::forget('rh-qr-scan:'.hash('sha256', $employe->qr_token));
        Carbon::setTestNow('2026-09-07 17:00:00');
        $this->actingAs($admin)->postJson(route('parametres.rh.presences.scan'), ['code' => $this->code($employe)])->assertOk();
        Cache::forget('rh-qr-scan:'.hash('sha256', $employe->qr_token));
        Carbon::setTestNow('2026-09-07 17:30:00');
        $this->actingAs($admin)->postJson(route('parametres.rh.presences.scan'), ['code' => $this->code($employe)])
            ->assertStatus(409)->assertJsonPath('message', 'Pointage déjà terminé pour aujourd’hui.');
    }

    public function test_qr_invalide_et_employe_inactif_sont_refuses(): void
    {
        [$admin, $employe] = $this->contexte();
        $this->actingAs($admin)->postJson(route('parametres.rh.presences.scan'), ['code' => 'rh-attendance:'.Str::random(64)])->assertStatus(422);
        $employe->update(['statut' => 'Inactif']);
        $this->actingAs($admin)->postJson(route('parametres.rh.presences.scan'), ['code' => $this->code($employe)])->assertStatus(422);
    }

    public function test_regeneration_invalide_immediatement_ancien_qr(): void
    {
        [$admin, $employe] = $this->contexte();
        $ancien = $employe->qr_token;
        $this->actingAs($admin)->patch(route('parametres.rh.employes.qr.regenerate', $employe))->assertRedirect();
        $this->assertNotSame($ancien, $employe->fresh()->qr_token);
        $this->actingAs($admin)->postJson(route('parametres.rh.presences.scan'), ['code' => 'rh-attendance:'.$ancien])->assertStatus(422);
    }

    public function test_correction_manuelle_est_motivee_et_journalisee(): void
    {
        [$admin, $employe] = $this->contexte();
        $presence = RhPresence::create(['entreprise_id' => $employe->entreprise_id, 'employe_id' => $employe->id, 'date' => '2026-09-07', 'heure_arrivee' => '08:15', 'heure_depart' => '17:00', 'statut' => 'Retard', 'statut_validation' => 'Validé']);

        $this->actingAs($admin)->patch(route('parametres.rh.presences.correct', $presence), ['heure_arrivee' => '08:00', 'heure_depart' => '17:00', 'statut' => 'Présent', 'motif_correction' => 'Erreur de lecture'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rh_workflow_historiques', ['workflowable_id' => $presence->id, 'action' => 'correction_pointage', 'motif' => 'Erreur de lecture', 'effectue_par' => $admin->id]);
        $this->assertSame('À vérifier', $presence->fresh()->statut_validation);
    }

    public function test_saisie_manuelle_historique_reste_compatible(): void
    {
        [$admin, $employe] = $this->contexte();
        $this->actingAs($admin)->post(route('parametres.rh.presences.store'), ['employe_id' => $employe->id, 'date' => '2026-09-06', 'heure_arrivee' => '08:00', 'heure_depart' => '17:00', 'statut' => 'Présent'])->assertRedirect()->assertSessionHasNoErrors();
        $presence = RhPresence::where('employe_id', $employe->id)->whereDate('date', '2026-09-06')->firstOrFail();
        $this->assertSame('Manuel', $presence->methode_pointage);
    }

    public function test_scan_restaure_une_presence_supprimee_sans_violer_la_contrainte_unique(): void
    {
        [$admin, $employe] = $this->contexte();
        $presence = RhPresence::create(['entreprise_id' => $employe->entreprise_id, 'employe_id' => $employe->id, 'date' => '2026-09-07', 'heure_arrivee' => '07:30', 'heure_depart' => '16:00', 'statut' => 'Présent']);
        $presence->delete();
        Carbon::setTestNow('2026-09-07 08:10:00');

        $this->actingAs($admin)->postJson(route('parametres.rh.presences.scan'), ['code' => $this->code($employe)])
            ->assertOk()->assertJsonPath('operation', 'ARRIVÉE');

        $presence->refresh();
        $this->assertNull($presence->deleted_at);
        $this->assertSame('08:10:00', $presence->heure_arrivee);
        $this->assertNull($presence->heure_depart);
        $this->assertDatabaseCount('rh_presences', 1);
    }

    public function test_le_menu_pointages_ouvre_directement_le_scanner(): void
    {
        [$admin] = $this->contexte();

        $this->actingAs($admin)->get(route('parametres.rh.presences'))
            ->assertOk()
            ->assertSee('Historique des présences')
            ->assertSee('Suivi des arrivées, départs, retards et modes de pointage.')
            ->assertSee(route('parametres.rh.presences.scanner'), false)
            ->assertSee('Pointages')
            ->assertSee('Présences')
            ->assertDontSee('Début pause')
            ->assertDontSee('Fin pause');
    }

    public function test_le_scanner_propose_un_mode_camera_compatible_mobile(): void
    {
        [$admin] = $this->contexte();

        $this->actingAs($admin)->get(route('parametres.rh.presences.scanner'))
            ->assertOk()
            ->assertSee('Scanner avec la caméra du téléphone')
            ->assertSee('capture="environment"', false)
            ->assertSee("facingMode:{ideal:'environment'}", false)
            ->assertSee('scanFile(file,true)', false)
            ->assertSee('const resultDisplayDuration=8000;', false);

        $this->actingAs($admin)->get('/parametres/ressources-humaines/presences/scanner,')
            ->assertRedirect(route('parametres.rh.presences.scanner'));
    }

    public function test_une_presence_en_brouillon_est_comptee_dans_la_synthese(): void
    {
        [$admin, $employe] = $this->contexte();
        RhPresence::create([
            'entreprise_id' => $employe->entreprise_id,
            'employe_id' => $employe->id,
            'date' => '2026-09-07',
            'heure_arrivee' => '08:15',
            'statut' => 'Retard',
            'retard_minutes' => 15,
            'statut_validation' => 'Brouillon',
        ]);

        $synthese = app(RhSyntheseMensuelleService::class)->generer($employe, 2026, 9, $admin->id);

        $this->assertSame(1, $synthese->jours_presents);
        $this->assertSame(1, $synthese->nombre_retards);
    }

    public function test_un_clic_genere_les_syntheses_de_tous_les_employes_actifs(): void
    {
        [$admin, $premier] = $this->contexte();
        Employe::create(['entreprise_id' => $premier->entreprise_id, 'matricule' => 'DG-2026-00002', 'nom' => 'Mwamba', 'prenom' => 'Aline', 'statut' => 'Actif', 'horaire_id' => $premier->horaire_id]);
        Employe::create(['entreprise_id' => $premier->entreprise_id, 'matricule' => 'DG-2026-00003', 'nom' => 'Inactif', 'statut' => 'Inactif']);

        $this->actingAs($admin)->post(route('parametres.rh.syntheses.store-all'), ['annee' => 2026, 'mois' => 9])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseCount('rh_syntheses_mensuelles', 2);
        $this->assertDatabaseHas('rh_syntheses_mensuelles', ['employe_id' => $premier->id, 'annee' => 2026, 'mois' => 9]);
        $this->actingAs($admin)->get(route('parametres.rh.syntheses'))->assertOk()->assertSee('Générer toutes les synthèses');
    }

    public function test_seul_le_super_admin_peut_modifier_et_supprimer_une_synthese(): void
    {
        [$superAdmin, $employe] = $this->contexte();
        $synthese = app(RhSyntheseMensuelleService::class)->generer($employe, 2026, 9, $superAdmin->id);
        $adminRole = Role::firstOrCreate(['designation' => 'Admin']);
        $admin = User::create(['nom' => 'Admin', 'prenom' => 'RH', 'email' => Str::random(8).'@test.local', 'password' => bcrypt('password'), 'role_id' => $adminRole->id, 'statut' => 'Actif', 'password_default' => false]);
        $valeurs = ['jours_ouvrables_prevus' => 22, 'jours_presents' => 7, 'jours_conges_payes' => 1, 'jours_mission' => 0, 'jours_absence_non_remuneree' => 0, 'nombre_retards' => 1, 'retard_total_minutes' => 10, 'heures_supplementaires_validees' => 2, 'motif_modification' => 'Correction administrative'];

        $this->actingAs($admin)->put(route('parametres.rh.syntheses.update', $synthese), $valeurs)->assertForbidden();
        $this->actingAs($superAdmin)->put(route('parametres.rh.syntheses.update', $synthese), $valeurs)->assertRedirect();
        $this->assertDatabaseHas('rh_syntheses_mensuelles', ['id' => $synthese->id, 'jours_presents' => 7, 'statut' => 'À vérifier']);

        $this->actingAs($admin)->delete(route('parametres.rh.syntheses.destroy', $synthese), ['motif_suppression' => 'Saisie erronée'])->assertForbidden();
        $this->actingAs($superAdmin)->delete(route('parametres.rh.syntheses.destroy', $synthese), ['motif_suppression' => 'Saisie erronée'])->assertRedirect();
        $this->assertDatabaseMissing('rh_syntheses_mensuelles', ['id' => $synthese->id]);
    }

    private function contexte(): array
    {
        Cache::flush();
        $role = Role::firstOrCreate(['designation' => 'Super Admin']);
        $admin = User::create(['nom' => 'Admin', 'prenom' => 'RH', 'email' => Str::random(8).'@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'statut' => 'Actif', 'password_default' => false]);
        $entreprise = Entreprise::create(['user_id' => $admin->id, 'nom_entreprise' => 'ARTICO']);
        $horaire = RhHoraire::create(['entreprise_id' => $entreprise->id, 'code' => 'TEST', 'libelle' => 'Horaire test', 'heure_debut' => '08:00', 'heure_fin' => '17:00', 'pause_minutes' => 60, 'heures_normales' => 8, 'tolerance_retard_minutes' => 0, 'par_defaut' => true, 'actif' => true]);
        $employe = Employe::create(['entreprise_id' => $entreprise->id, 'matricule' => 'EMP-QR-001', 'nom' => 'Kabeya', 'prenom' => 'Jean', 'statut' => 'Actif', 'horaire_id' => $horaire->id, 'qr_token' => Str::random(64), 'qr_genere_le' => now()]);

        return [$admin, $employe];
    }

    private function code(Employe $employe): string
    {
        return 'rh-attendance:'.$employe->qr_token;
    }
}
