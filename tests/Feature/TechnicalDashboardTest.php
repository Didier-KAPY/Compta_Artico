<?php

namespace Tests\Feature;

use App\Models\{Departement, EtatBesoin, Role, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_technical_officer_sees_general_management_needs_and_their_details(): void
    {
        $role = Role::firstOrCreate(['designation' => 'Chargé technique']);
        $user = User::create(['nom' => 'Test', 'prenom' => 'Technique', 'email' => 'tech-dg@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif']);
        $direction = Departement::create(['designation' => 'Direction générale']);
        $auteur = User::create(['nom' => 'Direction', 'prenom' => 'Générale', 'email' => 'dg@test.local', 'password' => bcrypt('password'), 'role_id' => Role::firstOrCreate(['designation' => 'Directeur Général'])->id, 'departement_id' => $direction->id, 'password_default' => false, 'statut' => 'Actif']);
        foreach ([$direction->id, null] as $index => $departementId) {
            $etats[] = EtatBesoin::create([
                'user_id' => $auteur->id, 'departement_id' => $departementId,
                'numero' => 'EB-DG-'.$index, 'date' => now()->toDateString(),
                'service' => 'Direction générale', 'demandeur' => 'Direction générale',
                'motif' => 'Besoin de la direction générale', 'monnaie' => 'CDF', 'statut' => 'En attente',
            ]);
        }

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertViewHas('total', 2)->assertViewHas('sansPiece', 2)
            ->assertSee('EB-DG-0')->assertSee('EB-DG-1');
        $this->get(route('etat-besoins.index', ['statut' => '', 'sans_piece' => 1]))
            ->assertOk()->assertSee('EB-DG-0')->assertSee('EB-DG-1');
        foreach ($etats as $etat) {
            $this->get(route('etat-besoins.show', $etat))->assertOk()->assertSee($etat->numero);
        }
    }

    public function test_counts_and_missing_documents_list_respect_the_technical_scope(): void
    {
        $role = Role::firstOrCreate(['designation' => 'Chargé technique']);
        $user = User::create(['nom' => 'Test', 'prenom' => 'Technique', 'email' => 'technical-dashboard@test.local', 'password' => bcrypt('password'), 'role_id' => $role->id, 'password_default' => false, 'statut' => 'Actif']);
        $technique = Departement::create(['designation' => 'Technique']);
        $finance = Departement::create(['designation' => 'Direction financière']);
        $create = fn ($numero, $attributes = []) => EtatBesoin::create(array_merge([
            'user_id' => $user->id, 'departement_id' => $technique->id, 'numero' => $numero,
            'date' => now()->toDateString(), 'service' => 'Technique', 'demandeur' => 'Agent', 'motif' => 'Test', 'monnaie' => 'CDF', 'statut' => 'En attente',
        ], $attributes));
        $create('EB-MISSING');
        $create('EB-EMPTY', ['piece_justificative' => '', 'pieces_justificatives' => [], 'statut' => 'Validé']);
        $create('EB-LEGACY', ['piece_justificative' => 'pieces/legacy.pdf']);
        $create('EB-MULTI', ['pieces_justificatives' => [['path' => 'pieces/new.pdf']], 'statut' => 'Rejeté']);
        $create('EB-FINANCE', ['departement_id' => $finance->id]);
        $create('EB-FINANCE-LEGACY', ['departement_id' => null, 'service' => 'Direction financière']);
        $create('EB-DELETED')->delete();
        $create('EB-PREVIOUS-MONTH', ['date' => now()->startOfMonth()->subDay()->toDateString()]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertViewHas('total', 4)->assertViewHas('sansPiece', 2)
            ->assertViewHas('statuts', fn ($counts) => $counts['En attente'] === 2 && $counts['Validé'] === 1 && $counts['Rejeté'] === 1)
            ->assertViewHas('derniersSansPiece', fn ($etats) => $etats->pluck('numero')->sort()->values()->all() === ['EB-EMPTY', 'EB-MISSING'])
            ->assertSee('EB-MISSING')->assertSee('EB-EMPTY')->assertDontSee('EB-FINANCE')
            ->assertDontSee('EB-DELETED')
            ->assertSee(route('parametres.rh.presences'), false);

        $this->get(route('dashboard', ['mois' => now()->startOfMonth()->subMonth()->format('Y-m')]))->assertOk()
            ->assertViewHas('total', 1)->assertViewHas('sansPiece', 1)
            ->assertSee('EB-PREVIOUS-MONTH')
            ->assertViewHas('derniersSansPiece', fn ($rows) => $rows->pluck('numero')->all() === ['EB-PREVIOUS-MONTH']);

        $this->get(route('etat-besoins.index', ['statut' => '', 'sans_piece' => 1]))->assertOk()
            ->assertViewHas('etatBesoins', fn ($etats) => $etats->pluck('numero')->sort()->values()->all() === ['EB-EMPTY', 'EB-MISSING', 'EB-PREVIOUS-MONTH'])
            ->assertSee('EB-MISSING')->assertSee('EB-EMPTY')->assertDontSee('EB-FINANCE')
            ->assertDontSee('EB-DELETED');
    }
}
