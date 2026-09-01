<?php

namespace Tests\Feature;

use App\Models\{Employe, Entreprise, RhConge, RhPaie, Role, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RessourceHumaineSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_permissions_rh_separent_consultation_gestion_et_paie(): void
    {
        $daf = $this->user('DAF');
        $admin = $this->user('Admin');

        $this->assertTrue(Gate::forUser($daf)->allows('viewHR'));
        $this->assertTrue(Gate::forUser($daf)->allows('managePayroll'));
        $this->assertFalse(Gate::forUser($daf)->allows('manageHR'));
        $this->assertTrue(Gate::forUser($admin)->allows('manageHR'));
        $this->assertFalse(Gate::forUser($admin)->allows('managePayroll'));
    }

    public function test_un_utilisateur_ne_peut_pas_traiter_son_propre_conge(): void
    {
        $admin = $this->user('Admin');
        $conge = RhConge::create(['user_id'=>$admin->id,'type'=>'Annuel','date_debut'=>'2026-09-01','date_fin'=>'2026-09-02']);

        $this->actingAs($admin)->patch(route('parametres.rh.conges.statut',$conge),['statut'=>'Approuvé'])
            ->assertSessionHasErrors('statut');
        $this->assertSame('En attente',$conge->fresh()->statut);
    }

    public function test_une_paie_payee_ne_peut_etre_remplacee_ni_supprimee(): void
    {
        $daf = $this->user('Super Admin');
        $utilisateur = $this->user('Comptable');
        $entreprise = Entreprise::create(['user_id'=>$daf->id,'nom_entreprise'=>'ARTICO']);
        $employe = Employe::create(['entreprise_id'=>$entreprise->id,'user_id'=>$utilisateur->id,'matricule'=>'EMP-001','nom'=>'Test','statut'=>'Actif']);
        $paie = RhPaie::create(['entreprise_id'=>$entreprise->id,'employe_id'=>$employe->id,'user_id'=>$utilisateur->id,'annee'=>2026,'mois'=>8,'salaire_base'=>1000,'primes'=>0,'retenues'=>0,'monnaie'=>'USD','statut'=>'Payée','date_paiement'=>'2026-08-31']);

        $this->actingAs($daf)->post(route('parametres.rh.paie.store'),['user_id'=>$utilisateur->id,'annee'=>2026,'mois'=>8,'salaire_base'=>2000,'monnaie'=>'USD','statut'=>'Payée','date_paiement'=>'2026-08-31','mode_paiement'=>'Cash','appliquer_retenues'=>1])
            ->assertSessionHasErrors('mois');
        $this->assertSame('1000.00',$paie->fresh()->salaire_base);
    }

    private function user(string $designation): User
    {
        $role = Role::firstOrCreate(['designation'=>$designation]);
        return User::create(['nom'=>'Test','prenom'=>$designation,'email'=>uniqid().'@example.test','password'=>bcrypt('password'),'role_id'=>$role->id,'password_default'=>false,'statut'=>'Actif']);
    }
}
