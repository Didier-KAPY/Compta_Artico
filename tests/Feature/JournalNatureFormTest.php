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

    public function test_chaque_page_affiche_les_journaux_en_attente_et_valides_de_sa_nature(): void
    {
        [$user, $types] = $this->contexte();

        foreach (['caisse' => 'caisse', 'banque' => 'banque', 'mobile' => 'mobile_money'] as $route => $nature) {
            Journaux::create([
                'user_id' => $user->id,
                'journal_type_id' => $types[$route]->id,
                'liste_des_comptes_id' => $types[$route]->liste_des_comptes_id,
                'reference' => 'ATTENTE-'.strtoupper($route),
                'date' => now(),
                'description' => 'Journal '.$nature,
                'monnaie' => 'CDF',
                'montant_ttc' => 116,
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
                'montant_ht' => 50,
                'statut' => 'Validé',
                'valide_par' => $user->id,
            ]);
        }

        foreach (['caisse', 'banque', 'mobile'] as $route) {
            $response = $this->actingAs($user)->get(route('journaux.create.'.$route));
            $response->assertOk()
                ->assertSee('Liste des journaux')
                ->assertSee('Total TTC')
                ->assertSee('Total HT')
                ->assertSee('Total TVA')
                ->assertSee('ATTENTE-'.strtoupper($route))
                ->assertSee('VALIDE-'.strtoupper($route))
                ->assertViewHas('totaux', fn ($totaux) =>
                    (float) $totaux['ht'] === 150.0
                    && (float) $totaux['tva'] === 16.0
                    && (float) $totaux['ttc'] === 116.0
                )
                ->assertViewHas('journaux', fn ($journaux) => $journaux->count() === 2
                    && $journaux->pluck('reference')->contains('ATTENTE-'.strtoupper($route))
                    && $journaux->pluck('reference')->contains('VALIDE-'.strtoupper($route)));
        }

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
