<?php

namespace Tests\Feature;

use App\Models\EcritureComptable;
use App\Models\JournalType;
use App\Models\ListeDesComptes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcritureOdSensTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_deux_sens_sappliquent_au_compte_du_journal(): void
    {
        foreach (['debit' => [250, 0, 0, 250], 'credit' => [0, 250, 250, 0]] as $sens => $attendu) {
            [$user, $type, $compteJournal, $compteImputation] = $this->contexte($sens);

            $this->actingAs($user)->post(route('ecritures.store'), [
                'date' => '2026-07-29', 'journal_type_id' => $type->id,
                'sens' => $sens, 'description' => 'Régularisation',
                'lignes' => [['compte_id' => $compteImputation->id, 'libelle' => 'Imputation', 'montant' => 250]],
            ])->assertRedirect(route('ecritures.create'));

            $journal = EcritureComptable::where('liste_des_comptes_id', $compteJournal->id)->latest('id')->firstOrFail();
            $imputation = EcritureComptable::where('liste_des_comptes_id', $compteImputation->id)->latest('id')->firstOrFail();
            $this->assertEquals($attendu, [(float) $journal->debit_cdf, (float) $journal->credit_cdf, (float) $imputation->debit_cdf, (float) $imputation->credit_cdf]);
        }
    }

    private function contexte(string $suffixe): array
    {
        $role = Role::firstOrCreate(['designation' => 'Comptable']);
        $user = User::create(['nom' => 'Test', 'prenom' => 'OD', 'email' => $suffixe.'@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => 0, 'statut' => 'Actif']);
        $compteJournal = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '4711'.$suffixe, 'designation' => 'Compte OD', 'nature' => 'Passif']);
        $compteImputation = ListeDesComptes::create(['user_id' => $user->id, 'compte' => '4111'.$suffixe, 'designation' => 'Client', 'nature' => 'Actif']);
        $type = JournalType::create(['user_id' => $user->id, 'code' => 'OD'.$suffixe, 'libelle' => 'Opérations diverses', 'liste_des_comptes_id' => $compteJournal->id, 'nature' => 'od', 'est_tresorerie' => false]);
        return [$user, $type, $compteJournal, $compteImputation];
    }
}
