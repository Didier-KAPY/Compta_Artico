<?php

namespace Tests\Feature;

use App\Models\BRC;
use App\Models\ClotureJournaliere;
use App\Models\EntreeCaisse;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\SortieCaisse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClotureJournaliereTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulation_est_sans_effet_et_cloture_regroupe_par_categorie(): void
    {
        [$user, $types, $compte] = $this->contexte();
        $this->journal($user, $types['caisse'], $compte, 'REC-1', 'recette', 'CDF', 100);
        $this->journal($user, $types['banque'], $compte, 'DEP-1', 'depense', 'USD', 40);
        $this->journal($user, $types['od'], $compte, 'OD-1', 'od', 'CDF', 25);

        $this->actingAs($user)->get(route('parametres.clotures.simulation', ['date' => '2026-08-05']))
            ->assertOk()->assertSee('REC-1')->assertSee('DEP-1')->assertSee('OD-1');
        $this->assertDatabaseCount('clotures_journalieres', 0);

        $response = $this->actingAs($user)->post(route('parametres.clotures.store'), ['date' => '2026-08-05']);
        $cloture = ClotureJournaliere::firstOrFail();
        $response->assertRedirect(route('parametres.clotures.show', $cloture));

        $this->assertSame('cloturee', $cloture->statut);
        $this->assertSame(3, $cloture->total_journaux);
        $this->assertDatabaseCount('entree_caisses', 1);
        $this->assertDatabaseCount('sortie_caisses', 1);
        $this->assertDatabaseCount('brcs', 1);
        $this->assertDatabaseCount('cloture_journaliere_journaux', 3);
        $this->assertSame(3, Journaux::where('statut_regroupement', 'regroupe')->count());
        $this->assertSame('cloture', EntreeCaisse::first()->origine);
        $this->assertSame('cloture', SortieCaisse::first()->origine);
        $this->assertSame('cloture', BRC::first()->origine);
        $this->assertSame('Validé', EntreeCaisse::first()->statut);
        $this->assertSame('Validé', SortieCaisse::first()->statut);
        $this->assertSame('Validé', BRC::first()->statut);

        $this->actingAs($user)->get(route('parametres.clotures.show', $cloture))
            ->assertOk()->assertSee(EntreeCaisse::first()->numero)->assertSee(SortieCaisse::first()->numero)->assertSee(BRC::first()->reference);

        $this->actingAs($user)->post(route('entree-caisses.valider', EntreeCaisse::first()), ['observation'=>'Validation quotidienne'])->assertRedirect();
        $this->actingAs($user)->post(route('sortie-caisses.valider', SortieCaisse::first()))->assertRedirect();
        $this->actingAs($user)->post(route('brc.valider', BRC::first()))->assertRedirect();
        $this->assertDatabaseCount('journaux', 3);
    }

    public function test_journal_deja_lie_est_exclu_et_complementaire_ne_reprend_pas_les_journaux(): void
    {
        [$user, $types, $compte] = $this->contexte();
        $bon = EntreeCaisse::create(['user_id'=>$user->id,'numero'=>'BEC-MANUEL','date'=>'2026-08-05','motif'=>'Manuel','montant'=>10,'monnaie'=>'CDF','statut'=>'Validé']);
        $lie = $this->journal($user, $types['caisse'], $compte, 'LIE-1', 'recette', 'CDF', 10);
        $lie->update(['entree_caisse_id' => $bon->id]);
        $libre = $this->journal($user, $types['caisse'], $compte, 'LIBRE-1', 'recette', 'CDF', 20);

        $this->actingAs($user)->post(route('parametres.clotures.store'), ['date'=>'2026-08-05'])->assertRedirect();
        $this->assertSame('non_regroupe', $lie->fresh()->statut_regroupement);
        $this->assertSame('regroupe', $libre->fresh()->statut_regroupement);

        $this->actingAs($user)->post(route('parametres.clotures.store'), [
            'date'=>'2026-08-05', 'complementaire'=>1, 'motif'=>'Complément après la clôture principale.',
        ])->assertSessionHasErrors('date');
        $this->assertDatabaseCount('clotures_journalieres', 1);
    }

    public function test_non_super_admin_ne_peut_pas_acceder_au_dashboard(): void
    {
        [$user] = $this->contexte('Comptable');
        $this->actingAs($user)->get(route('parametres.clotures.index'))->assertForbidden();
    }

    public function test_super_admin_peut_reouvrir_une_journee_pour_regularisation(): void
    {
        [$user, $types, $compte] = $this->contexte();
        $journal = $this->journal($user, $types['caisse'], $compte, 'REGUL-1', 'recette', 'CDF', 100);

        $this->actingAs($user)->post(route('parametres.clotures.store'), ['date' => '2026-08-05'])->assertRedirect();
        $cloture = \App\Models\ClotureJournaliere::firstOrFail();
        $journal->update(['statut' => 'Validé']);

        $this->actingAs($user)->post(route('parametres.clotures.reouvrir', $cloture), [
            'motif' => 'Régularisation d’une opération omise.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $cloture->refresh();
        $this->assertSame('reouverte', $cloture->statut);
        $this->assertSame('Régularisation d’une opération omise.', $cloture->motif_reouverture);
    }

    public function test_suppression_definitive_detache_un_journal_de_sa_cloture_et_conserve_la_trace(): void
    {
        [$user, $types, $compte] = $this->contexte();
        $journal = $this->journal($user, $types['caisse'], $compte, 'CLOT-FORCE', 'recette', 'CDF', 100);
        $cloture = ClotureJournaliere::create([
            'numero_cloture' => 'CL-TEST-FORCE',
            'date_comptable' => '2026-08-05',
            'statut' => 'cloturee',
        ]);
        $cloture->rattachements()->create([
            'journal_id' => $journal->id,
            'categorie_document' => 'RECETTE',
            'type_tresorerie' => 'caisse',
            'regroupe_le' => now(),
        ]);
        $journal->forceFill(['motif_suppression'=>'Correction définitive contrôlée.','supprime_par'=>$user->id])->save();
        $journal->delete();

        $this->actingAs($user)->delete(route('corbeille.force-delete', ['journaux', $journal->id]), [
            'motif' => 'Suppression définitive du journal rattaché.',
            'confirmation_comptable' => '1',
            'phrase_confirmation' => 'SUPPRIMER DÉFINITIVEMENT',
        ])->assertRedirect(route('corbeille.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('journaux', ['id'=>$journal->id]);
        $this->assertDatabaseMissing('cloture_journaliere_journaux', ['journal_id'=>$journal->id]);
        $this->assertDatabaseHas('audit_logs', [
            'model_type'=>Journaux::class,
            'model_id'=>$journal->id,
            'action'=>'suppression_definitive',
        ]);
        $this->assertDatabaseHas('clotures_journalieres', ['id'=>$cloture->id]);
    }

    private function contexte(string $roleName = 'Super Admin'): array
    {
        $role = Role::create(['designation' => $roleName]);
        $user = User::create(['nom'=>'Admin','prenom'=>'Clôture','email'=>uniqid().'@test.local','password'=>bcrypt('password'),'role_id'=>$role->id,'password_default'=>0,'statut'=>'Actif']);
        $compte = ListeDesComptes::create(['user_id'=>$user->id,'compte'=>'471100','designation'=>'Imputation','nature'=>'Actif']);
        $types = [];
        foreach ([['caisse','CAI',true],['banque','BQ',true],['od','OD',false]] as [$nature,$code,$tresorerie]) {
            $types[$nature] = JournalType::create(['user_id'=>$user->id,'code'=>$code,'libelle'=>$nature,'liste_des_comptes_id'=>$compte->id,'nature'=>$nature,'monnaie'=>'CDF','est_tresorerie'=>$tresorerie]);
        }
        return [$user, $types, $compte];
    }

    private function journal(User $user, JournalType $type, ListeDesComptes $compte, string $reference, string $operation, string $monnaie, float $montant): Journaux
    {
        return Journaux::create([
            'user_id'=>$user->id,'journal_type_id'=>$type->id,'liste_des_comptes_id'=>$compte->id,
            'reference'=>$reference,'date'=>'2026-08-05','description'=>$reference,'type'=>$operation,
            'monnaie'=>$monnaie,'mode_paiement'=>$type->nature==='banque'?'banque':'espèces','montant_ttc'=>$montant,
            'entrees_cdf'=>$operation==='recette'&&$monnaie==='CDF'?$montant:0,
            'entrees_usd'=>$operation==='recette'&&$monnaie==='USD'?$montant:0,
            'sorties_cdf'=>$operation==='depense'&&$monnaie==='CDF'?$montant:0,
            'sorties_usd'=>$operation==='depense'&&$monnaie==='USD'?$montant:0,
            'statut'=>'En attente','statut_regroupement'=>'non_regroupe',
        ]);
    }
}
