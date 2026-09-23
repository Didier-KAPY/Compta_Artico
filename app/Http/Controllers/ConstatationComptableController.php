<?php

namespace App\Http\Controllers;

use App\Models\{EcritureComptable, ListeDesComptes};
use App\Services\{ConstatationComptableService, CurrentEntreprise};
use Illuminate\Http\Request;

class ConstatationComptableController extends Controller
{
    public function show(Request $request, int $id, ConstatationComptableService $service)
    {
        $source = EcritureComptable::with(['journal.journalType', 'journal.entreeCaisse', 'journal.sortieCaisse', 'journal.constatation', 'compte', 'user', 'constatation.lignes.compte', 'constatation.reglement.compte', 'constatation.user'])->findOrFail($id);
        $company = $service->assertCompany($source, $request->user());
        $constatation = $source->constatation ?? $source->journal->constatation;
        if (!$constatation) abort_unless($service->eligible($source), 422, 'Cette opération ne peut plus être constatée.');
        $comptes = ListeDesComptes::orderBy('compte')->get()->filter(fn($a)=>$service->companyId($a) === $company);
        $snapshot = $constatation?->instantane_source ?? $service->snapshot($source);
        $entrepriseActive = app(CurrentEntreprise::class)->for($request->user());
        $lignesExistantes = $source->journal->ecritures()->with('compte')
            ->orderByRaw('CASE WHEN debit_cdf > 0 THEN 0 ELSE 1 END')->orderBy('id')->get();
        return view('Comptabilite.ecritures.constatation', compact('source', 'constatation', 'comptes', 'snapshot', 'entrepriseActive', 'lignesExistantes'));
    }

    public function store(Request $request, int $id, ConstatationComptableService $service)
    {
        $data = $request->validate([
            'date'=>'required|date', 'type_operation'=>'required|in:salaire,generique,charge,produit',
            'compte_liaison_id'=>'required|integer|exists:liste_des_comptes,id',
            'lignes'=>'required|array|min:2', 'lignes.*.liste_des_comptes_id'=>'required|integer|exists:liste_des_comptes,id',
            'lignes.*.nature'=>'required|in:charge,retenue,dette,autre', 'lignes.*.libelle'=>'required|string|max:255',
            'lignes.*.debit_cdf'=>'required|numeric|min:0|max:9999999999999|decimal:0,2',
            'lignes.*.credit_cdf'=>'required|numeric|min:0|max:9999999999999|decimal:0,2',
        ]);
        $record = $service->store($id, $data, $request);
        return redirect()->route('ecritures.constatation', $record->source_ecriture_id)->with('success', 'Constatation validée et règlement complété.');
    }
}
