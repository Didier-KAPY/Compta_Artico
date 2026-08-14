<?php

namespace App\Services;

use App\Models\LigneBudgetaire;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetExecutionService
{
    public function enrichir(Collection $lignes): Collection
    {
        if ($lignes->isEmpty()) return $lignes;

        $realisations = DB::table('lignes_budgetaires as lb')
            ->join('rubriques_budgetaires as rb', 'rb.id', '=', 'lb.rubrique_budgetaire_id')
            ->leftJoin('ecritures_comptables as ec', function ($join) {
                $join->on('ec.liste_des_comptes_id', '=', 'rb.liste_des_comptes_id')
                    ->on('ec.date', '>=', 'lb.date_debut')->on('ec.date', '<=', 'lb.date_fin')
                    ->where('ec.statut', '=', 'Validé')->whereNull('ec.deleted_at');
            })
            ->whereIn('lb.id', $lignes->pluck('id'))->whereNull('lb.deleted_at')
            ->groupBy('lb.id','rb.nature')
            ->select('lb.id','rb.nature')
            ->selectRaw("COALESCE(SUM(CASE WHEN rb.nature = 'RECETTE' THEN ec.credit_cdf ELSE ec.debit_cdf END), 0) AS realise")
            ->get()->keyBy('id');

        return $lignes->each(function (LigneBudgetaire $ligne) use ($realisations) {
            $resultat = $realisations->get($ligne->id);
            $nature = $resultat?->nature ?? $ligne->rubriqueBudgetaire?->nature ?? 'DEPENSE';
            $realise = max((float) ($resultat?->realise ?? 0), 0);
            $revise = $ligne->budget_revise;
            $engage = $nature === 'DEPENSE' ? (float) $ligne->engagements_actifs : 0;
            $ligne->setAttribute('nature_budgetaire', $nature);
            $ligne->setAttribute('realise_comptable', $realise);
            $ligne->setAttribute('ecart_comptable', $revise - $realise);
            $ligne->setAttribute('reste_a_realiser', max($revise - $realise, 0));
            $ligne->setAttribute('disponible_comptable', $nature === 'DEPENSE' ? $revise - $engage - $realise : null);
            $ligne->setAttribute('taux_execution_comptable', $revise > 0 ? ($realise / $revise) * 100 : 0);
            $ligne->setAttribute('taux_mobilisation_comptable', $nature === 'DEPENSE' && $revise > 0 ? (($engage + $realise) / $revise) * 100 : 0);
        });
    }

    public function synthese(Collection $lignes): array
    {
        $recettes = $lignes->where('nature_budgetaire','RECETTE');
        $depenses = $lignes->where('nature_budgetaire','DEPENSE');
        $previsionRecettes = (float) $recettes->sum(fn($l) => $l->budget_revise);
        $recettesRealisees = (float) $recettes->sum('realise_comptable');
        $previsionDepenses = (float) $depenses->sum(fn($l) => $l->budget_revise);
        $depensesRealisees = (float) $depenses->sum('realise_comptable');
        $engagements = (float) $depenses->sum('engagements_actifs');
        return [
            'recettes'=>['prevision'=>$previsionRecettes,'realise'=>$recettesRealisees,'reste'=>max($previsionRecettes-$recettesRealisees,0),'taux'=>$previsionRecettes>0?$recettesRealisees/$previsionRecettes*100:0],
            'depenses'=>['initial'=>(float)$depenses->sum('prevision_initiale'),'revise'=>$previsionDepenses,'engage'=>$engagements,'realise'=>$depensesRealisees,'disponible'=>$previsionDepenses-$engagements-$depensesRealisees,'taux'=>$previsionDepenses>0?$depensesRealisees/$previsionDepenses*100:0,'mobilisation'=>$previsionDepenses>0?($engagements+$depensesRealisees)/$previsionDepenses*100:0],
            'solde_previsionnel'=>$previsionRecettes-$previsionDepenses,
            'solde_realise'=>$recettesRealisees-$depensesRealisees,
            'ecart_solde'=>($recettesRealisees-$depensesRealisees)-($previsionRecettes-$previsionDepenses),
        ];
    }

    public function realisePourLigne(LigneBudgetaire $ligne): float
    {
        return (float) $this->enrichir(collect([$ligne->loadMissing('rubriqueBudgetaire')]))->first()->realise_comptable;
    }
}
