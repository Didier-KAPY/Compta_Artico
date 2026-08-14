<?php

namespace Tests\Feature;

use App\Models\BudgetExercice;
use App\Models\Departement;
use App\Models\Entreprise;
use App\Models\EtatBesoin;
use App\Models\LigneBudgetaire;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\SortieCaisse;
use App\Models\TauxDeChange;
use App\Models\User;
use App\Models\RubriqueBudgetaire;
use App\Models\EcritureComptable;
use App\Models\JournalType;
use App\Models\Journaux;
use App\Services\BudgetService;
use App\Services\BudgetExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;

class BudgetWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_engagement_cdf_est_unique_et_diminue_le_disponible(): void
    {
        [$user, $ligne] = $this->contexte(1_000_000);
        $etat = $this->etat($user, $ligne, 300_000, 'CDF');
        $service = app(BudgetService::class);

        $premier = $service->engagerEtat($etat);
        $second = $service->engagerEtat($etat);

        $this->assertSame($premier->id, $second->id);
        $this->assertEquals(300_000, $ligne->fresh()->engagements_actifs);
        $this->assertEquals(700_000, $ligne->fresh()->disponible);
        $this->assertDatabaseCount('engagements_budgetaires', 1);
        $this->assertDatabaseHas('mouvements_budgetaires', ['type'=>'engagement','montant'=>300000]);
    }

    public function test_engagement_usd_conserve_le_taux_historique(): void
    {
        [$user, $ligne, $entreprise] = $this->contexte(2_000_000);
        TauxDeChange::create(['user_id'=>$user->id,'entreprise_id'=>$entreprise->id,'devise_source'=>'USD','devise_cible'=>'CDF','taux_de_change'=>2850,'date_taux'=>'2026-08-05']);
        $etat = $this->etat($user, $ligne, 500, 'USD');

        $engagement = app(BudgetService::class)->engagerEtat($etat);
        TauxDeChange::create(['user_id'=>$user->id,'entreprise_id'=>$entreprise->id,'devise_source'=>'USD','devise_cible'=>'CDF','taux_de_change'=>2900,'date_taux'=>'2026-08-06']);

        $this->assertEquals(500, $engagement->montant_original);
        $this->assertSame('USD', $engagement->monnaie_originale);
        $this->assertEquals(2850, $engagement->taux_change);
        $this->assertEquals(1_425_000, $engagement->montant_budgetaire);
        $this->assertSame('2026-08-05', $engagement->date_taux->toDateString());
    }

    public function test_budget_insuffisant_bloque_engagement(): void
    {
        [$user, $ligne] = $this->contexte(100_000);
        $etat = $this->etat($user, $ligne, 120_000, 'CDF');

        $this->expectException(ValidationException::class);
        try { app(BudgetService::class)->engagerEtat($etat); }
        finally { $this->assertDatabaseCount('engagements_budgetaires', 0); }
    }

    public function test_validation_sortie_transforme_engagement_en_realisation_sans_double_consommation(): void
    {
        [$user, $ligne] = $this->contexte(1_000_000);
        $etat = $this->etat($user, $ligne, 300_000, 'CDF');
        $service = app(BudgetService::class);
        $engagement = $service->engagerEtat($etat);
        $sortie = SortieCaisse::create(['user_id'=>$user->id,'numero'=>'BSC-26080001','date'=>'2026-08-05','etat_besoin_id'=>$etat->id,'beneficiaire'=>'Test','motif'=>'Paiement','montant'=>300000,'monnaie'=>'CDF','statut'=>'Validé','type'=>'Caisse']);

        $premiere = $service->realiserSortie($sortie);
        $seconde = $service->realiserSortie($sortie);

        $this->assertSame($premiere->id, $seconde->id);
        $this->assertEquals(0, $ligne->fresh()->engagements_actifs);
        $this->assertEquals(300_000, $ligne->fresh()->realisations);
        $this->assertEquals(700_000, $ligne->fresh()->disponible);
        $this->assertSame('Réalisé', $engagement->fresh()->statut);
        $this->assertDatabaseCount('realisations_budgetaires', 1);
    }

    public function test_suppression_definitive_archive_la_realisation_avant_de_supprimer_la_sortie(): void
    {
        Config::set('features.budget', true);
        [$user, $ligne] = $this->contexte(1_000_000);
        $etat = $this->etat($user, $ligne, 300_000, 'CDF');
        $service = app(BudgetService::class);
        $service->engagerEtat($etat);
        $sortie = SortieCaisse::create(['user_id'=>$user->id,'numero'=>'BSC-FORCE-BUDGET','date'=>'2026-08-05','etat_besoin_id'=>$etat->id,'beneficiaire'=>'Test','motif'=>'Paiement','montant'=>300000,'monnaie'=>'CDF','statut'=>'Validé','type'=>'Caisse']);
        $realisation = $service->realiserSortie($sortie);
        $sortie->forceFill(['motif_suppression'=>'Suppression définitive contrôlée.','supprime_par'=>$user->id])->save();
        $sortie->delete();
        Config::set('features.budget', false);

        $this->actingAs($user)->delete(route('corbeille.force-delete', ['bons-sortie', $sortie->id]), [
            'motif'=>'Suppression définitive avec archivage budgétaire.',
            'confirmation_comptable'=>'1',
            'phrase_confirmation'=>'SUPPRIMER DÉFINITIVEMENT',
        ])->assertRedirect(route('corbeille.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sortie_caisses', ['id'=>$sortie->id]);
        $this->assertDatabaseMissing('realisations_budgetaires', ['id'=>$realisation->id]);
        $audit = \App\Models\AuditLog::where('model_type', SortieCaisse::class)->where('model_id', $sortie->id)->where('action', 'suppression_definitive')->firstOrFail();
        $this->assertSame('Réalisation budgétaire archivée', $audit->dependances[0]['type']);
    }

    public function test_revision_et_transfert_conservent_les_formules_et_historique(): void
    {
        [$user,$source,$entreprise,$departement]=$this->contexte(1_000_000);
        $compte=ListeDesComptes::create(['user_id'=>$user->id,'compte'=>'602100','designation'=>'Entretien','nature'=>'Charge']);
        $destination=LigneBudgetaire::create(['budget_exercice_id'=>$source->budget_exercice_id,'entreprise_id'=>$entreprise->id,'departement_id'=>$departement->id,'liste_des_comptes_id'=>$compte->id,'code'=>'ENTR','rubrique'=>'Entretien','prevision_initiale'=>500000,'statut'=>'Active','created_by'=>$user->id]);
        $service=app(BudgetService::class);

        $service->reviser($source,200000,'positive','Révision autorisée pour carburant');
        $service->transferer($source,$destination,100000,'Transfert autorisé vers entretien');

        $this->assertEquals(1_100_000,$source->fresh()->budget_revise);
        $this->assertEquals(600_000,$destination->fresh()->budget_revise);
        $this->assertEquals(1_100_000,$source->fresh()->disponible);
        $this->assertDatabaseHas('mouvements_budgetaires',['type'=>'révision_positive','montant'=>200000]);
        $this->assertDatabaseHas('mouvements_budgetaires',['type'=>'transfert_sortant','montant'=>100000]);
        $this->assertDatabaseHas('mouvements_budgetaires',['type'=>'transfert_entrant','montant'=>100000]);
    }

    public function test_repartition_utilise_uniquement_les_mois_de_la_periode(): void
    {
        [$user,$ligne]=$this->contexte(500000);
        $ligne->update(['date_debut'=>'2026-08-01','date_fin'=>'2026-12-31']);

        $this->actingAs($user)->post(route('parametres.budgets.lignes.mensualiser',$ligne),['mode'=>'egale'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('mensualites_budgetaires',5);
        foreach([8,9,10,11,12] as $mois) $this->assertDatabaseHas('mensualites_budgetaires',['ligne_budgetaire_id'=>$ligne->id,'mois'=>$mois,'montant'=>100000]);
        $this->assertDatabaseMissing('mensualites_budgetaires',['ligne_budgetaire_id'=>$ligne->id,'mois'=>1]);
    }

    public function test_recette_reelle_utilise_les_credits_des_ecritures_validees(): void
    {
        [$user,, $entreprise, $departement]=$this->contexte(100000);
        $compte=ListeDesComptes::create(['user_id'=>$user->id,'compte'=>'706100','designation'=>'Prestations','nature'=>'Produit']);
        $rubrique=RubriqueBudgetaire::create(['code'=>'REC-PREST','designation'=>'Prestations','nature'=>'RECETTE','liste_des_comptes_id'=>$compte->id,'actif'=>true,'created_by'=>$user->id]);
        $budget=BudgetExercice::first();
        $ligne=LigneBudgetaire::create(['budget_exercice_id'=>$budget->id,'entreprise_id'=>$entreprise->id,'departement_id'=>$departement->id,'liste_des_comptes_id'=>$compte->id,'rubrique_budgetaire_id'=>$rubrique->id,'code'=>'REC-1','rubrique'=>'Prestations','date_debut'=>'2026-01-01','date_fin'=>'2026-12-31','prevision_initiale'=>100000,'statut'=>'Active','created_by'=>$user->id]);
        $type=JournalType::create(['user_id'=>$user->id,'code'=>'VTE','libelle'=>'Ventes','nature'=>'vente']);
        $journal=Journaux::create(['user_id'=>$user->id,'journal_type_id'=>$type->id,'reference'=>'VTE-1','date'=>'2026-08-05','description'=>'Vente','type'=>'vente','monnaie'=>'CDF','statut'=>'Validé']);
        EcritureComptable::create(['user_id'=>$user->id,'journal_id'=>$journal->id,'liste_des_comptes_id'=>$compte->id,'date'=>'2026-08-05','piece'=>'VTE-1','libelle'=>'Recette validée','debit_cdf'=>5000,'credit_cdf'=>70000,'statut'=>'Validé']);
        EcritureComptable::create(['user_id'=>$user->id,'journal_id'=>$journal->id,'liste_des_comptes_id'=>$compte->id,'date'=>'2026-08-05','piece'=>'VTE-2','libelle'=>'Attente','debit_cdf'=>0,'credit_cdf'=>90000,'statut'=>'En attente']);
        $supprimee=EcritureComptable::create(['user_id'=>$user->id,'journal_id'=>$journal->id,'liste_des_comptes_id'=>$compte->id,'date'=>'2026-08-05','piece'=>'VTE-3','libelle'=>'Supprimée','debit_cdf'=>0,'credit_cdf'=>80000,'statut'=>'Validé']); $supprimee->delete();
        $calculee=app(BudgetExecutionService::class)->enrichir(collect([$ligne->load('rubriqueBudgetaire')]))->first();
        $this->assertEquals(70000,$calculee->realise_comptable);
        $this->assertEquals(30000,$calculee->ecart_comptable);
        $this->assertEquals(30000,$calculee->reste_a_realiser);
        $this->assertEquals(70,$calculee->taux_execution_comptable);
    }

    private function contexte(float $prevision): array
    {
        $role=Role::create(['designation'=>'Super Admin']);
        $user=User::create(['nom'=>'Budget','prenom'=>'Test','email'=>uniqid().'@test.local','password'=>bcrypt('password'),'role_id'=>$role->id,'password_default'=>false,'statut'=>'Actif']);
        $this->actingAs($user);
        $entreprise=Entreprise::create(['user_id'=>$user->id,'nom_entreprise'=>'ARTICO','monnaie_budgetaire'=>'CDF']);
        $departement=Departement::create(['designation'=>'Finance']);
        $compte=ListeDesComptes::create(['user_id'=>$user->id,'compte'=>'601100','designation'=>'Achats','nature'=>'Charge']);
        $budget=BudgetExercice::create(['entreprise_id'=>$entreprise->id,'exercice'=>2026,'libelle'=>'Budget 2026','monnaie'=>'CDF','montant_initial'=>$prevision,'statut'=>'Validé','created_by'=>$user->id]);
        $rubrique=RubriqueBudgetaire::create(['code'=>'DEP-CARB','designation'=>'Carburant','nature'=>'DEPENSE','liste_des_comptes_id'=>$compte->id,'actif'=>true,'created_by'=>$user->id]);
        $ligne=LigneBudgetaire::create(['budget_exercice_id'=>$budget->id,'entreprise_id'=>$entreprise->id,'departement_id'=>$departement->id,'liste_des_comptes_id'=>$compte->id,'rubrique_budgetaire_id'=>$rubrique->id,'code'=>'CARB','rubrique'=>'Carburant','date_debut'=>'2026-01-01','date_fin'=>'2026-12-31','prevision_initiale'=>$prevision,'statut'=>'Active','created_by'=>$user->id]);
        return [$user,$ligne,$entreprise,$departement];
    }

    private function etat(User $user, LigneBudgetaire $ligne, float $montant, string $monnaie): EtatBesoin
    {
        return EtatBesoin::create(['user_id'=>$user->id,'departement_id'=>$ligne->departement_id,'ligne_budgetaire_id'=>$ligne->id,'numero'=>uniqid('EB-'),'date'=>'2026-08-05','service'=>'Finance','demandeur'=>'Test','motif'=>'Besoin','montant_estime'=>$montant,'monnaie'=>$monnaie,'statut'=>'En attente']);
    }
}
