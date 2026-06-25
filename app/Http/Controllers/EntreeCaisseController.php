<?php

namespace App\Http\Controllers;

use App\Models\EntreeCaisse;
use App\Models\EntreeCaisseLigne;
use App\Models\Journaux;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntreeCaisseController extends Controller
{
    public function index()
    {
        $entrees = EntreeCaisse::with('user')
            ->latest()
            ->paginate(10);

        return view('entree_caisses.index', compact('entrees'));
    }

    public function create()
    {
        return view('entree_caisses.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'date' => 'required|date',
        'motif' => 'required|string',
        'monnaie' => 'required|string',
        //'type' => 'required|string|in:Caisse,Banque,Monnaie électronique',
        'designation.*' => 'required|string',
        'quantite.*' => 'required|numeric',
        'prix_unitaire.*' => 'required|numeric',
    ]);

    DB::beginTransaction();

    try {

        // 🔥 CREATE ENTRÉE
        $entree = EntreeCaisse::create([
            'numero' => $this->generateNumero(),
            'user_id' => auth()->id(),
            'date' => $request->date,
            'motif' => $request->motif,
            'monnaie' => $request->monnaie,
            //'type' => $request->type,
            'statut' => 'En attente',
            'montant' => 0
        ]);

        $total = 0;

        foreach ($request->designation as $key => $designation) {

            $qty = (float) ($request->quantite[$key] ?? 0);
            $price = (float) ($request->prix_unitaire[$key] ?? 0);

            $montant = $qty * $price;

            EntreeCaisseLigne::create([
                'entree_caisse_id' => $entree->id,
                'designation' => $designation,
                'quantite' => $qty,
                'prix_unitaire' => $price,
                'montant' => $montant,
            ]);

            $total += $montant;
        }

        // 🔥 UPDATE TOTAL
        $entree->update([
            'montant' => $total
        ]);

        DB::commit();

        // ✅ IMPORTANT : retour sans redirection liste
        return back()->with('success', '✔ Bon d’entrée créé avec succès');

    } catch (\Exception $e) {

        DB::rollBack();

        // 🔥 IMPORTANT POUR DEBUG
        return back()->with('error', 'Erreur : ' . $e->getMessage());
    }
}

    public function show($id)
    {
        $entree = EntreeCaisse::with(['user', 'lignes'])->findOrFail($id);

        return view('entree_caisses.show', compact('entree'));
    }

    // =========================
    // VALIDATION TOGGLE
    // =========================
    public function valider($id, $type = 'entree')
{
    DB::beginTransaction();

    try {

        // =========================
        // ENTREE CAISSE
        // =========================
        if($type == 'entree') {
            $caisse = EntreeCaisse::with('lignes')
                ->findOrFail($id);
            $montant = $caisse->lignes->sum(function($ligne){
                return ($ligne->quantite ?? 0) *
                       ($ligne->prix_unitaire ?? 0);
            });
            if($montant <= 0){
                $montant = $caisse->montant ?? 0;
            }
            // RETOUR EN ATTENTE
            if($caisse->statut == 'Validé'){
                $caisse->update([
                    'statut'=>'En attente',
                    'date_validation'=>null,
                    'valide_par'=>null
                ]);
                Journaux::where(
                    'entree_caisse_id',
                    $caisse->id
                )->delete();
            }else{
                // VALIDATION
                $caisse->update([
                    'statut'=>'Validé',
                    'montant'=>$montant,
                    'date_validation'=>now(),
                    'valide_par'=>auth()->id()
                ]);
                Journaux::updateOrCreate(
                    [
                        'entree_caisse_id'=>$caisse->id
                    ],
                    [
                        'user_id'=>auth()->id(),
                        // LIAISON
                        'entree_caisse_id'=>$caisse->id,
                        'sortie_caisse_id'=>null,
                        'reference'=>$caisse->numero,
                        'date'=>$caisse->date,
                        'description'=>$caisse->motif,
                        'piece_justificatif'=>$caisse->numero,
                        'mode_paiement'=>'Espèces',
                        'monnaie'=>$caisse->monnaie,
                        'entrees_cdf'=>
                        $caisse->monnaie=='CDF'
                        ? $montant : 0,
                        'entrees_usd'=>
                        $caisse->monnaie=='USD'
                        ? $montant : 0,
                        'sorties_cdf'=>0,
                        'sorties_usd'=>0,
                        'statut'=>'En attente'
                    ]
                );
            }
        }
        // =========================
        // SORTIE CAISSE
        // =========================
        else {
            $sortie = SortieCaisse::findOrFail($id);
            if($sortie->statut == 'Validé'){
                $sortie->update([
                    'statut'=>'En attente',
                    'date_validation'=>null,
                    'valide_par'=>null

                ]);
                Journaux::where(
                    'sortie_caisse_id',
                    $sortie->id
                )->delete();
            }else{
                $sortie->update([
                    'statut'=>'Validé',
                    'date_validation'=>now(),
                    'valide_par'=>auth()->id()

                ]);

                Journaux::updateOrCreate(
                    [
                        'sortie_caisse_id'=>$sortie->id
                    ],
                    [
                        'user_id'=>auth()->id(),
                        // LIAISON
                        'entree_caisse_id'=>null,
                        'sortie_caisse_id'=>$sortie->id,
                        'reference'=>$sortie->numero,
                        'date'=>$sortie->date,
                        'description'=>$sortie->motif,
                        'piece_justificatif'=>$sortie->numero,
                        'mode_paiement'=>'Espèces',
                        'monnaie'=>$sortie->monnaie,
                        'entrees_cdf'=>0,
                        'entrees_usd'=>0,
                        'sorties_cdf'=>
                        $sortie->monnaie=='CDF'
                        ? $sortie->montant : 0,
                        'sorties_usd'=>
                        $sortie->monnaie=='USD'
                        ? $sortie->montant : 0,
                        'statut'=>'En attente'
                    ]
                );
            }
        }
        DB::commit();
        return back()->with(
            'success',
            'Validation effectuée avec succès.'
        );
    } catch(\Exception $e){
        DB::rollBack();
        return back()->with(
            'error',
            $e->getMessage()
        );
    }
}

    public function rejeter($id)
{
    $entree = EntreeCaisse::findOrFail($id);

    if ($entree->statut === 'Rejeté') {

        $entree->update([
            'statut' => 'En attente'
        ]);

    } else {

        $entree->update([
            'statut' => 'Rejeté'
        ]);
    }

    return back()->with('success', 'Statut mis à jour.');
}

    // =========================
    // GENERATE NUMERO
    // =========================
    private function generateNumero()
{
    $prefix = now()->format('ym'); // 2606

    $last = EntreeCaisse::where('numero', 'like', $prefix . '%')
        ->latest('id')
        ->first();

    $next = 1;

    if ($last) {

        $lastNumero = substr($last->numero, 4);

        if (is_numeric($lastNumero)) {
            $next = ((int) $lastNumero) + 1;
        }
    }

    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

public function statistiques(Request $request)
{
    $currentYear = date('Y');
    $years = range($currentYear - 5, $currentYear + 2);

    // 🔥 récupération propre
    $year = $request->input('year', $currentYear);
    $month = $request->input('month');

    // 🔥 NORMALISATION (IMPORTANT)
    $month = ($month === "" || $month === null) ? null : (int)$month;

    // 📌 QUERY UNIQUE
    $query = EntreeCaisse::whereYear('created_at', $year);

    if ($month) {
        $query->whereMonth('created_at', $month);
    }

    // 📊 KPI (TOUJOURS FILTRÉS)
    $totalEntrees = (clone $query)->count();
    $totalValidees = (clone $query)->where('statut', 'valide')->count();
    $totalRejetees = (clone $query)->where('statut', 'rejete')->count();
    $enAttente = $totalEntrees - ($totalValidees + $totalRejetees);

    // 📊 GRAPH
    $labels = [];
    $values = [];

    if ($month) {

        // 📅 PAR JOUR (MOIS ACTIF)
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($d = 1; $d <= $days; $d++) {

            $labels[] = $d;

            $values[] = (clone $query)
                ->whereDay('created_at', $d)
                ->count();
        }

    } else {

        // 📅 PAR MOIS (ANNÉE COMPLETE)
        for ($m = 1; $m <= 12; $m++) {

            $labels[] = "M" . $m;

            $values[] = EntreeCaisse::whereYear('created_at', $year)
                ->whereMonth('created_at', $m)
                ->count();
        }
    }

    return view('entree_caisses.statistiques', compact(
        'years',
        'year',
        'month',
        'totalEntrees',
        'totalValidees',
        'totalRejetees',
        'enAttente',
        'labels',
        'values'
    ));
}
public function edit($id)
{
    $entree = EntreeCaisse::with('lignes')->findOrFail($id);

    return view('entree_caisses.edit', compact('entree', 'statut'));
}
public function update(Request $request, $id)
{
    
    DB::beginTransaction();

    try {

        $entree = EntreeCaisse::findOrFail($id);

        // 🚨 VERIFICATION SIMPLE DU STATUT DANS JOURNAUX
            $statut = \App\Models\Journaux::where('reference', $entree->numero)
    ->value('statut');

    if ($statut === 'valide') {

        return redirect()
            ->back()
            ->with('error', '❌ Modification impossible : ce journal est déjà validé, les fonds ont été sortis.');
    }

        // ================= CALCUL TOTAL =================
        $total = collect($request->lignes ?? [])->sum(function ($l) {
            return ($l['quantite'] ?? 0) * ($l['prix_unitaire'] ?? 0);
        });

        // ================= UPDATE ENTREE =================
        $entree->update([
            'numero' => $request->numero ?? $entree->numero,
            'date' => $request->date,
            'motif' => $request->motif,
        //'type' => $request->type,
            'monnaie' => $request->monnaie,
            'observation' => $request->observation,
            'montant' => $total,

            // retour automatique en attente
            'statut' => 'en attente',
        ]);

        // ================= SUPPRIMER ANCIENNES LIGNES =================
        $entree->lignes()->delete();

        // ================= RECREER LIGNES =================
        if ($request->has('lignes')) {

            foreach ($request->lignes as $ligne) {

                if (!empty($ligne['designation'])) {

                    $entree->lignes()->create([
                        'designation' => $ligne['designation'],
                        'quantite' => $ligne['quantite'] ?? 0,
                        'prix_unitaire' => $ligne['prix_unitaire'] ?? 0,
                        'montant' => ($ligne['quantite'] ?? 0) * ($ligne['prix_unitaire'] ?? 0),
                    ]);
                }
            }
        }

        DB::commit();

        return redirect()
            ->route('entree-caisse.index')
            ->with('success', '✅ Entrée modifiée avec succès');

    } catch (\Exception $e) {

        DB::rollBack();

        return back()->with('error', $e->getMessage());
    }
}
}