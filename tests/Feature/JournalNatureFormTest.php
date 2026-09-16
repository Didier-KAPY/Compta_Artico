<?php

namespace Tests\Feature;

use App\Models\EntreeCaisse;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\TauxDeChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalNatureFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_chaque_page_filtre_la_liste_et_totalise_uniquement_les_valides_sans_filtres(): void
    {
        [$user, $types] = $this->contexte();

        foreach (['caisse' => 'caisse', 'banque' => 'banque', 'mobile' => 'mobile_money'] as $route => $nature) {
            Journaux::create([
                'user_id' => $user->id,
                'journal_type_id' => $types[$route]->id,
                'liste_des_comptes_id' => $types[$route]->liste_des_comptes_id,
                'reference' => 'ATTENTE-'.strtoupper($route),
                'date' => now(),
                'nom_partenaire' => 'Partenaire '.strtoupper($route),
                'description' => 'Journal '.$nature,
                'monnaie' => 'CDF',
                'montant_ttc' => 116,
                'entrees_cdf' => 116,
                'montant_ht' => 100,
                'taux_tva' => 16,
                'montant_tva' => 16,
                'statut' => 'En attente',
            ]);            Journaux::create([
                'user_id' => $user->id,
                'journal_type_id' => $types[$route]->id,
                'liste_des_comptes_id' => $types[$route]->liste_des_comptes_id,
                'reference' => 'VALIDE-'.strtoupper($route),
                'date' => now(),
                'description' => 'Journal validé '.$nature,
                'monnaie' => 'CDF',
                'montant_ttc' => 50,
                'entrees_cdf' => 50,
                'montant_ht' => 50,
                'statut' => 'Validé',
                'valide_par' => $user->id,
            ]);
            Journaux::create([
                'user_id' => $user->id,
                'journal_type_id' => $types[$route]->id,
                'liste_des_comptes_id' => $types[$route]->liste_des_comptes_id,
                'reference' => 'USD-'.strtoupper($route),
                'date' => now(),
                'description' => 'Journal USD '.$nature,
                'monnaie' => 'USD',
                'montant_ttc' => 23.20,
                'sorties_usd' => 23.20,
                'montant_ht' => 20,
                'taux_tva' => 16,
                'montant_tva' => 3.20,
                'statut' => 'Validé',
            ]);
        }

        foreach (['caisse', 'banque', 'mobile'] as $route) {
            $response = $this->actingAs($user)->get(route('journaux.create.'.$route));
            $response->assertOk()
                ->assertSee('Validé par')
                ->assertSee('Liste des journaux')
                ->assertSee('Total TTC')
                ->assertSee('Total HT')
                ->assertSee('Total TVA')
                ->assertSee('CDF')
                ->assertSee('USD')
                ->assertSee('ATTENTE-'.strtoupper($route))
                ->assertDontSee('VALIDE-'.strtoupper($route))
                ->assertSee('Partenaire '.strtoupper($route))
                ->assertViewHas('totaux', fn ($totaux) =>
                    (float) $totaux['CDF']['ht'] === 50.0
                    && (float) $totaux['CDF']['tva'] === 0.0
                    && (float) $totaux['CDF']['ttc'] === 0.0
                    && (float) $totaux['USD']['ht'] === 20.0
                    && (float) $totaux['USD']['tva'] === 3.2
                    && (float) $totaux['USD']['ttc'] === 23.2
                )
                ->assertViewHas('montantsComptes', fn ($montants) =>
                    $montants['CDF'] === ['entrees' => 50.0, 'sorties' => 0.0, 'solde' => 50.0]
                    && $montants['USD'] === ['entrees' => 0.0, 'sorties' => 23.2, 'solde' => -23.2])
                ->assertViewHas('journaux', fn ($journaux) => $journaux->count() === 1
                    && $journaux->pluck('reference')->contains('ATTENTE-'.strtoupper($route)));

            foreach ([['statut' => 'Validé'], ['statut' => ''], [
                'reference' => 'INTROUVABLE',
                'date_debut' => '2000-01-01',
                'date_fin' => '2000-01-02',
            ]] as $filtres) {
                $this->get(route('journaux.create.'.$route, $filtres))
                    ->assertOk()
                    ->assertViewHas('totaux', $response->viewData('totaux'))
                    ->assertViewHas('montantsComptes', $response->viewData('montantsComptes'));
            }
        }

        $roleComptable = Role::create(['designation' => 'Comptable']);
        $user->update(['role_id' => $roleComptable->id]);
        $user->refresh();

        $this->actingAs($user)->get(route('journaux.create.caisse'))
            ->assertOk()
            ->assertDontSee('Validé par');

        $this->actingAs($user)->get(route('journaux.create'))
            ->assertRedirect(route('journaux.index'));
    }

    public function test_index_filtre_par_journal_de_tresorerie(): void
    {
        [$user, $types] = $this->contexte();

        Journaux::create(['user_id' => $user->id, 'journal_type_id' => $types['caisse']->id, 'liste_des_comptes_id' => $types['caisse']->liste_des_comptes_id, 'reference' => 'REF-CAISSE', 'date' => '2026-07-29', 'statut' => 'Validé']);
        Journaux::create(['user_id' => $user->id, 'journal_type_id' => $types['banque']->id, 'liste_des_comptes_id' => $types['banque']->liste_des_comptes_id, 'reference' => 'REF-BANQUE', 'date' => '2026-07-29', 'statut' => 'Validé']);

        $response = $this->actingAs($user)->get(route('journaux.index', ['journal_type_id' => $types['banque']->id]));
        $response->assertOk()->assertSee('Journal / compte de trésorerie')->assertSee('521100')
            ->assertSee('REF-BANQUE')
            ->assertViewHas('journaux', fn ($journaux) => $journaux->every(
                fn ($journal) => $journal->journal_type_id === $types['banque']->id
            ));
    }

    public function test_index_affiche_le_journal_le_plus_recent_en_premier(): void
    {
        [$user, $types] = $this->contexte();
        $attributes = [
            'user_id' => $user->id,
            'journal_type_id' => $types['caisse']->id,
            'liste_des_comptes_id' => $types['caisse']->liste_des_comptes_id,
            'statut' => 'En attente',
        ];

        Journaux::create($attributes + ['reference' => 'REF-ANCIEN', 'date' => '2026-07-28']);
        Journaux::create($attributes + ['reference' => 'REF-RECENT-1', 'date' => '2026-07-30']);
        Journaux::create($attributes + ['reference' => 'REF-RECENT-2', 'date' => '2026-07-30']);

        $this->actingAs($user)->get(route('journaux.index'))
            ->assertOk()
            ->assertSeeInOrder(['REF-RECENT-2', 'REF-RECENT-1', 'REF-ANCIEN']);
    }

    public function test_index_est_pagine_par_dix_journaux(): void
    {
        [$user, $types] = $this->contexte();

        foreach (range(1, 12) as $numero) {
            Journaux::create([
                'user_id' => $user->id,
                'journal_type_id' => $types['caisse']->id,
                'liste_des_comptes_id' => $types['caisse']->liste_des_comptes_id,
                'reference' => 'REF-PAGE-'.str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
                'date' => '2026-07-30',
                'statut' => 'En attente',
            ]);
        }

        $this->actingAs($user)->get(route('journaux.index'))
            ->assertOk()
            ->assertViewHas('journaux', fn ($journaux) => $journaux->perPage() === 10 && $journaux->lastPage() === 2)
            ->assertSee('sur 12 journaux');

        $this->actingAs($user)->get(route('journaux.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('REF-PAGE-01');
    }

    public function test_monnaie_selectionne_automatiquement_le_compte_du_journal(): void
    {
        [$user, $types] = $this->contexte();
        TauxDeChange::create(['user_id' => $user->id, 'taux_de_change' => 2800]);
        $operation = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '701100', 'designation' => 'Produit', 'nature' => 'Produit']);

        $this->actingAs($user)->post(route('journaux.store'), [
            'journal_nature' => 'banque',
            'journal_type_id' => $types['banque']->id,
            'liste_des_comptes_id' => $operation->id,
            'date' => '2026-07-29', 'type' => 'recette', 'monnaie' => 'USD',
            'montant_ttc' => 100, 'appliquer_tva' => 0, 'mode_paiement' => 'banque',
            'description' => 'Recette USD',
        ])->assertRedirect(route('journaux.index'));

        $this->assertDatabaseHas('journaux', [
            'journal_type_id' => $types['banque_usd']->id,
            'monnaie' => 'USD',
            'statut_regroupement' => 'regroupe',
        ]);
        $this->assertDatabaseCount('entree_caisses', 1);
        $this->assertSame(EntreeCaisse::firstOrFail()->id, Journaux::firstOrFail()->entree_caisse_id);
    }

    public function test_les_recus_ne_sont_pas_affiches_pour_les_bons_de_sortie(): void
    {
        [$user, $types] = $this->contexte();

        foreach (['caisse' => 'BSC', 'banque' => 'BSB', 'mobile' => 'BSM'] as $route => $prefixe) {
            $journal = Journaux::create([
                'user_id' => $user->id,
                'journal_type_id' => $types[$route]->id,
                'liste_des_comptes_id' => $types[$route]->liste_des_comptes_id,
                'reference' => $prefixe.'-TEST',
                'date' => '2026-09-09',
                'description' => 'Bon de sortie',
                'monnaie' => 'CDF',
                'montant_ttc' => 100,
                'montant_ht' => 100,
                'statut' => 'En attente',
            ]);

            $this->actingAs($user)->get(route('journaux.create.'.$route))
                ->assertOk()
                ->assertSee($journal->reference)
                ->assertDontSee(route('journaux.recu', $journal), false)
                ->assertDontSee(route('journaux.recu.pdf', $journal), false)
                ->assertDontSee('Imprimer le reçu')
                ->assertDontSee('Télécharger le reçu');
        }

        $journalOrdinaire = Journaux::create([
            'user_id' => $user->id,
            'journal_type_id' => $types['caisse']->id,
            'liste_des_comptes_id' => $types['caisse']->liste_des_comptes_id,
            'reference' => 'CAI-TEST',
            'date' => '2026-09-09',
            'description' => 'Journal ordinaire',
            'monnaie' => 'CDF',
            'montant_ttc' => 100,
            'montant_ht' => 100,
            'statut' => 'En attente',
        ]);

        $this->actingAs($user)->get(route('journaux.create.caisse'))
            ->assertOk()
            ->assertSee(route('journaux.recu', $journalOrdinaire), false)
            ->assertSee(route('journaux.recu.pdf', $journalOrdinaire), false);
    }

    private function contexte(): array
    {
        $role = Role::create(['designation' => 'Super Admin']);
        $user = User::create(['nom' => 'Test', 'prenom' => 'Journaux', 'email' => 'journaux@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => 0, 'statut' => 'Actif']);
        $definitions = [
            'caisse' => ['CAI', '571100', 'Caisse'],
            'banque' => ['BQ', '521100', 'Banque'],
            'mobile_money' => ['MOB', '532100', 'Mobile Money'],
        ];
        $types = [];
        foreach ($definitions as $nature => [$code, $numero, $designation]) {
            $compte = ListeDesComptes::create(['user_id' => $user->id, 'compte' => $numero, 'designation' => $designation, 'nature' => 'Actif']);
            $types[$nature === 'mobile_money' ? 'mobile' : $nature] = JournalType::create(['user_id' => $user->id, 'code' => $code, 'libelle' => $designation, 'liste_des_comptes_id' => $compte->id, 'nature' => $nature, 'est_tresorerie' => true]);
        }
        $compteUsd = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '521200', 'designation' => 'Banque USD', 'nature' => 'Actif']);
        $types['banque_usd'] = JournalType::create(['user_id' => $user->id, 'code' => 'BQUSD', 'libelle' => 'Banque USD', 'liste_des_comptes_id' => $compteUsd->id, 'nature' => 'banque', 'monnaie' => 'USD', 'est_tresorerie' => true]);
        return [$user, $types];
    }
}
