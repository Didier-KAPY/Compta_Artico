<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Paiement;
use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaiementController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'facture_id' => 'required|exists:factures,id',
            'liste_des_comptes_id' => 'required|exists:liste_des_comptes,id',
            'montant' => 'required|numeric|min:0.01',
            'mode_paiement' => 'required',
            'journal_type_id' => 'required',
        ]);

        DB::transaction(function () use ($request) {

            $facture = Facture::findOrFail($request->facture_id);

            // Création du paiement
            $paiement = Paiement::create([
                'facture_id' => $facture->id,
                'liste_des_comptes_id' => $request->liste_des_comptes_id,
                'id_user' => auth()->id(),
                'montant' => $request->montant,
                'mode_paiement' => $request->mode_paiement,
                'date_paiement' => now(),
            ]);

            // Mise à jour facture
            $facture->montant_paye += $request->montant;

            $facture->reste_a_payer =
                $facture->montant_total - $facture->montant_paye;

            if ($facture->reste_a_payer <= 0) {

                $facture->reste_a_payer = 0;
                $facture->statut = 'paye';

            } elseif ($facture->montant_paye > 0) {

                $facture->statut = 'partiel';
            }

            $facture->save();

            // Déterminer devise du compte
            $compte = \App\Models\ListeDesCompte::find(
                $request->liste_des_comptes_id
            );

            $entreeCDF = 0;
            $entreeUSD = 0;

            if (
                str_contains(
                    strtoupper($compte->libelle ?? ''),
                    'USD'
                )
            ) {
                $entreeUSD = $request->montant;
            } else {
                $entreeCDF = $request->montant;
            }

            // Journal
            Journal::create([
                'user_id' => auth()->id(),

                'liste_des_comptes_id' =>
                    $request->liste_des_comptes_id,

                'journal_type_id' =>
                    $request->journal_type_id,

                'facture_id' => $facture->id,

                'paiement_id' => $paiement->id,

                'date' => now(),

                'reference' =>
                    $facture->numero_facture,

                'description' =>
                    'Paiement facture N° ' .
                    $facture->numero_facture,

                'entrees_cdf' => $entreeCDF,
                'entrees_usd' => $entreeUSD,

                'sorties_cdf' => 0,
                'sorties_usd' => 0,
            ]);
        });

        return redirect()
            ->back()
            ->with(
                'success',
                'Paiement enregistré avec succès.'
            );
    }
}