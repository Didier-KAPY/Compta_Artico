<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\FactureDetail;
use Illuminate\Http\Request;

class FactureController extends Controller
{
    public function index()
    {
        $factures = Facture::latest()->get();
        return view('facture.index', compact('factures'));
    }

    public function create()
    {
        return view('facture.create');
    }

    public function store(Request $request)
    {
        $facture = Facture::create([
            'numero_facture' => 'FAC-' . time(),
            'nom_client' => $request->nom_client,
            'contact_client' => $request->contact_client,
            'montant_total' => 0,
            'montant_paye' => 0,
            'reste_a_payer' => 0,
            'date_facture' => now(),
        ]);

        $total = 0;

        foreach ($request->details as $item) {

            $montant = $item['quantite'] * $item['prix_unitaire'];

            FactureDetail::create([
                'facture_id' => $facture->id,
                'libelle' => $item['libelle'],
                'quantite' => $item['quantite'],
                'prix_unitaire' => $item['prix_unitaire'],
                'montant_ligne' => $montant,
            ]);

            $total += $montant;
        }

        $facture->update([
            'montant_total' => $total,
            'reste_a_payer' => $total
        ]);

        return redirect()->route('factures.index');
    }

    public function show($id)
    {
        $facture = Facture::with('details', 'paiements')->findOrFail($id);
        return view('facture.show', compact('facture'));
    }
}
