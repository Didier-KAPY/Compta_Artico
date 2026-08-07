<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EtatBesoin;
use App\Services\DocumentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequences_incrementent_et_redemarrent_par_mois(): void
    {
        $service = app(DocumentNumberService::class);

        $this->assertSame('BEC-26080001', $service->next('BEC', '2026-08-05', 'Caisse'));
        $this->assertSame('BEC-26080002', $service->next('BEC', '2026-08-31', 'Caisse'));
        $this->assertSame('BEC-26090001', $service->next('BEC', '2026-09-01', 'Caisse'));
    }

    public function test_sequences_sont_separees_par_entreprise_et_tresorerie(): void
    {
        $service = app(DocumentNumberService::class);

        $this->assertSame('BSC-26080001', $service->next('BSC', '2026-08-05', 'Caisse', 1));
        $this->assertSame('BSC-B-26080001', $service->next('BSC', '2026-08-05', 'Banque', 1));
        $this->assertSame('BSC-M-26080001', $service->next('BSC', '2026-08-05', 'Mobile Money', 1));
        $this->assertSame('BSC-26080001', $service->next('BSC', '2026-08-05', 'Caisse', 2));
    }

    public function test_brc_conserve_la_convention_existante(): void
    {
        $service = app(DocumentNumberService::class);

        $this->assertSame('BRC-20260804-000001', $service->next('BRC', '2026-08-04'));
        $this->assertSame('BRC-20260804-000002', $service->next('BRC', '2026-08-04'));
    }

    public function test_etat_de_besoin_utilise_aussi_une_sequence_mensuelle(): void
    {
        $service = app(DocumentNumberService::class);

        $this->assertSame('EB-0001-26-08', $service->next('EB', '2026-08-05'));
        $this->assertSame('EB-0002-26-08', $service->next('EB', '2026-08-06'));
        $this->assertSame('EB-0001-26-09', $service->next('EB', '2026-09-01'));
    }

    public function test_compteur_reprend_apres_les_documents_historiques_absents_de_la_table_de_sequences(): void
    {
        $departement = Departement::create(['designation' => 'Administration']);
        EtatBesoin::create([
            'departement_id' => $departement->id, 'numero' => 'EB-0042-26-08',
            'date' => '2026-08-05', 'service' => 'Administration', 'demandeur' => 'Historique',
            'motif' => 'Document antérieur', 'monnaie' => 'CDF', 'montant_estime' => 0, 'statut' => 'En attente',
        ]);

        $this->assertSame('EB-0043-26-08', app(DocumentNumberService::class)->next('EB', '2026-08-05'));
    }
}
