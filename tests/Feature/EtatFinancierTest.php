<?php

namespace Tests\Feature;

use App\Models\BilanInitial;
use App\Models\EcritureComptable;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\User;
use App\Services\SyscohadaEtatFinancierService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EtatFinancierTest extends TestCase
{
    use RefreshDatabase;

    public function test_mouvements_valides_de_tous_les_utilisateurs_sont_agreges_uniquement_en_cdf(): void
    {
        $user = $this->user('Comptable');
        $autre = $this->user('Comptable');
        $vente = $this->compte($user, '701100', 'Ventes principales', ' Produit ', ' Gestion ');
        $achat = $this->compte($user, '601100', 'Achats', 'Charge', 'Gestion');
        $this->ecriture($user, $vente, '2026-06-10', 0, 100, 'Validé');
        $this->ecriture($user, $achat, '2026-06-10', 40, 0, 'Validé');
        $this->ecriture($user, $vente, '2026-06-10', 0, 1000, 'En attente');
        $this->ecriture($user, $vente, '2026-06-10', 0, 2000, 'Rejeté');
        $this->ecriture($autre, $this->compte($autre, '706900', 'Ventes autre utilisateur', 'Produit', 'Gestion'), '2026-06-10', 0, 200, 'Validé');
        $this->ecriture($user, $vente, '2025-06-10', 0, 3000, 'Validé');

        $etat = $this->service()->generer(new CarbonImmutable('2026-01-01'), new CarbonImmutable('2026-12-31'));
        $ligne = collect($etat['compte_resultat']['produits_exploitation']['lignes'])->firstWhere('code', '701100');

        $this->assertSame('CDF', $etat['monnaie']);
        $this->assertSame(260.0, $etat['compte_resultat']['resultat_net']['actuel']);
        $this->assertSame(40.0, $etat['controles']['total_debit']);
        $this->assertSame(300.0, $etat['controles']['total_credit']);
        $this->assertSame('Ventes principales', $ligne['label']);
        $this->assertSame(' Produit ', $ligne['nature']);
        $this->assertSame(' Gestion ', $ligne['observation']);
    }

    public function test_nature_et_observation_de_liste_des_comptes_pilotent_le_classement(): void
    {
        $user = $this->user('DAF');
        $this->ecriture($user, $this->compte($user, '999101', 'Actif dynamique', ' actif ', ' bilan '), '2026-03-01', 100, 0, 'Validé');
        $this->ecriture($user, $this->compte($user, '999102', 'Passif dynamique', 'PASSIF', 'BILAN'), '2026-03-01', 0, 100, 'Validé');
        $this->ecriture($user, $this->compte($user, '999103', 'Charge dynamique', 'Charge', 'Gestion'), '2026-03-01', 30, 0, 'Validé');
        $this->ecriture($user, $this->compte($user, '999104', 'Produit dynamique', 'Produit', 'Gestion'), '2026-03-01', 0, 50, 'Validé');
        $this->ecriture($user, $this->compte($user, '281100', 'Amortissement dynamique', 'Actif', 'Bilan'), '2026-03-01', 0, 20, 'Validé');

        $etat = $this->service()->generer(new CarbonImmutable('2026-01-01'), new CarbonImmutable('2026-12-31'));
        $actif = collect($etat['bilan']['actif'])->pluck('lignes')->flatten(1);
        $passif = collect($etat['bilan']['passif'])->pluck('lignes')->flatten(1);

        $this->assertNotNull($actif->firstWhere('code', '999101'));
        $this->assertNotNull($passif->firstWhere('code', '999102'));
        $this->assertNotNull(collect($etat['compte_resultat']['charges_exploitation']['lignes'])->firstWhere('code', '999103'));
        $this->assertNotNull(collect($etat['compte_resultat']['produits_exploitation']['lignes'])->firstWhere('code', '999104'));
        $this->assertSame(-20.0, $actif->firstWhere('code', '281100')['actuel']);
    }

    public function test_compte_mal_parametre_est_signale_et_prefixe_reste_un_secours(): void
    {
        $user = $this->user('DAF');
        $compte = $this->compte($user, '571100', 'Caisse incohérente', 'Actif', 'Gestion');
        $this->ecriture($user, $compte, '2026-03-01', 25, 0, 'Validé');

        $etat = $this->service()->generer(new CarbonImmutable('2026-01-01'), new CarbonImmutable('2026-12-31'));

        $this->assertSame('Compte mal paramétré dans liste_des_comptes', $etat['anomalies'][0]['raison']);
        $this->assertSame('571100', $etat['anomalies'][0]['compte']);
        $this->assertNotNull(collect($etat['bilan']['actif'])->pluck('lignes')->flatten(1)->firstWhere('code', '571100'));
    }

    public function test_un_compte_avec_un_solde_nul_ne_figure_pas_dans_le_bilan(): void
    {
        $user = $this->user('DAF');
        $compteNul = $this->compte($user, '571200', 'Caisse soldée', 'Actif', 'Bilan');
        $compteNonNul = $this->compte($user, '571300', 'Caisse active', 'Actif', 'Bilan');
        $this->ecriture($user, $compteNul, '2026-03-01', 100, 0, 'Validé');
        $this->ecriture($user, $compteNul, '2026-03-02', 0, 100, 'Validé');
        $this->ecriture($user, $compteNonNul, '2026-03-03', 50, 0, 'Validé');

        $etat = $this->service()->generer(new CarbonImmutable('2026-01-01'), new CarbonImmutable('2026-12-31'));
        $lignes = collect($etat['bilan']['actif'])->pluck('lignes')->flatten(1);

        $this->assertNull($lignes->firstWhere('code', '571200'));
        $this->assertNotNull($lignes->firstWhere('code', '571300'));
        $this->assertSame(50.0, $etat['bilan']['total_actif']);
    }

    public function test_ecriture_sans_compte_est_signalee(): void
    {
        $user = $this->user('DAF');
        $ecriture = $this->ecriture($user, $this->compte($user, '571100', 'Caisse', 'Actif', 'Bilan'), '2026-03-01', 25, 0, 'Validé');
        DB::statement('PRAGMA defer_foreign_keys = ON');
        EcritureComptable::whereKey($ecriture->id)->update(['liste_des_comptes_id' => 999999]);

        $etat = $this->service()->generer(new CarbonImmutable('2026-01-01'), new CarbonImmutable('2026-12-31'));

        $this->assertSame('Écriture sans compte comptable associé', $etat['anomalies'][0]['raison']);
        $this->assertSame(25.0, $etat['anomalies'][0]['debit']);
    }

    public function test_role_non_autorise_recoit_403(): void
    {
        $this->actingAs($this->user('Caissier'))->get('/comptabilite/etats-financiers')->assertForbidden();
    }

    public function test_urls_des_etats_sont_accessibles_au_daf(): void
    {
        $this->actingAs($this->user('DAF'));
        $this->get('/comptabilite/etats-financiers')->assertOk();
        $this->get('/comptabilite/etats-financiers/bilan')->assertOk();
        $this->get('/comptabilite/etats-financiers/compte-resultat')->assertOk();
    }

    public function test_un_daf_peut_archiver_le_bilan_initial_affiche(): void
    {
        $user = $this->user('DAF');
        $compte = $this->compte($user, '571100', 'Caisse', 'Actif', 'Bilan');
        $this->ecriture($user, $compte, '2026-03-01', 125, 0, 'Validé');

        $this->actingAs($user)
            ->post('/comptabilite/etats-financiers/bilan/archiver', [
                'libelle' => 'Bilan initial 2026',
                'date_debut' => '2026-01-01',
                'date_fin' => '2026-12-31',
            ])
            ->assertRedirect('/comptabilite/etats-financiers/bilan?date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertSessionHas('success');

        $archive = BilanInitial::firstOrFail();
        $this->assertSame('Bilan initial 2026', $archive->libelle);
        $this->assertSame($user->id, $archive->user_id);
        $this->assertSame('125.00', $archive->total_actif);
        $this->assertSame(125.0, (float) $archive->donnees['bilan']['total_actif']);

        $this->get('/comptabilite/etats-financiers/bilan?date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertOk()
            ->assertSee('Consulter le bilan initial')
            ->assertDontSee('Libellé de l’archive');

        $this->get(route('comptabilite.etats-financiers.bilan-initial', $archive))
            ->assertOk()
            ->assertViewIs('Comptabilite.etats_financiers.bilan_initial')
            ->assertSee('Bilan initial 2026')
            ->assertSee('125,00')
            ->assertSee('Supprimer')
            ->assertDontSee('Actualiser')
            ->assertDontSee('name="date_debut"', false)
            ->assertDontSee('name="date_fin"', false);

        $this->delete(route('comptabilite.etats-financiers.bilan-initial.supprimer', $archive))
            ->assertRedirect('/comptabilite/etats-financiers/bilan?date_debut=2026-01-01&date_fin=2026-12-31')
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('bilan_initials', ['id' => $archive->id]);
    }

    private function service(): SyscohadaEtatFinancierService
    {
        return app(SyscohadaEtatFinancierService::class);
    }

    private function user(string $designation): User
    {
        $role = Role::firstOrCreate(['designation' => $designation]);

        return User::create(['nom' => 'Test', 'prenom' => $designation, 'email' => uniqid().'@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => 0, 'statut' => 'Actif']);
    }

    private function compte(User $user, string $numero, string $designation, ?string $nature, ?string $observation): ListeDesComptes
    {
        return ListeDesComptes::create(['user_id' => $user->id, 'compte' => $numero, 'designation' => $designation, 'nature' => $nature, 'observation' => $observation]);
    }

    private function ecriture(User $user, ListeDesComptes $compte, string $date, float $dc, float $cc, string $statut): EcritureComptable
    {
        $type = JournalType::create(['user_id' => $user->id, 'code' => uniqid('OD'), 'libelle' => 'OD', 'nature' => 'od']);
        $journal = Journaux::create(['user_id' => $user->id, 'journal_type_id' => $type->id, 'liste_des_comptes_id' => $compte->id, 'reference' => uniqid('J'), 'date' => $date, 'type' => 'od', 'monnaie' => 'CDF', 'statut' => 'Validé']);

        return EcritureComptable::create(['user_id' => $user->id, 'journal_id' => $journal->id, 'liste_des_comptes_id' => $compte->id, 'date' => $date, 'libelle' => 'Test', 'debit_cdf' => $dc, 'credit_cdf' => $cc, 'statut' => $statut]);
    }
}
