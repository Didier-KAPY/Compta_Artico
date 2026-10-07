<?php

namespace App\Services;

use App\Models\Journaux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TreasuryMovementService
{
    public function positions(string $debut, string $fin): \Illuminate\Support\Collection
    {
        return $this->query()->select('journal_type_id')->with('journalType.compte')
            ->where('statut', 'Validé')->whereHas('journalType', fn ($query) => $query->where('est_tresorerie', true))
            ->whereDate('date', '<=', $fin)
            ->selectRaw('SUM(CASE WHEN DATE(date) < ? THEN entrees_cdf - sorties_cdf ELSE 0 END) AS ouverture_cdf', [$debut])
            ->selectRaw('SUM(CASE WHEN DATE(date) < ? THEN entrees_usd - sorties_usd ELSE 0 END) AS ouverture_usd', [$debut])
            ->selectRaw('SUM(CASE WHEN DATE(date) >= ? THEN entrees_cdf ELSE 0 END) AS entree_cdf', [$debut])
            ->selectRaw('SUM(CASE WHEN DATE(date) >= ? THEN sorties_cdf ELSE 0 END) AS sortie_cdf', [$debut])
            ->selectRaw('SUM(CASE WHEN DATE(date) >= ? THEN entrees_usd ELSE 0 END) AS entree_usd', [$debut])
            ->selectRaw('SUM(CASE WHEN DATE(date) >= ? THEN sorties_usd ELSE 0 END) AS sortie_usd', [$debut])
            ->selectRaw('SUM(entrees_cdf - sorties_cdf) AS solde_cdf, SUM(entrees_usd - sorties_usd) AS solde_usd')
            ->groupBy('journal_type_id')->get();
    }

    /** Read-only view of physical treasury movements; BRCs are excluded. */
    public function query(): Builder
    {
        $columns = ['id', 'user_id', 'journal_type_id', 'liste_des_comptes_id',
            'reference', 'date', 'description', 'type', 'monnaie', 'statut',
            'entree_caisse_id', 'sortie_caisse_id', 'deleted_at',
            'entrees_cdf', 'sorties_cdf', 'entrees_usd', 'sorties_usd'];
        $ordinary = DB::table('journaux')->select($columns);
        $ordinary->whereNotExists(fn ($q) => $q->selectRaw('1')->from('brcs')
            ->whereColumn('brcs.journal_id', 'journaux.id'))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('brc_journal')
                ->whereColumn('brc_journal.journal_id', 'journaux.id'));

        return Journaux::query()->fromSub(
            $ordinary->unionAll($this->accountingCounterparts()),
            'journaux'
        );
    }

    private function accountingCounterparts(): \Illuminate\Database\Query\Builder
    {
        // Recover the historical conversion from the balanced journal, never
        // from today's exchange rate. Split imputations retain their share.
        $totals = DB::table('ecritures_comptables')->whereNull('deleted_at')
            ->selectRaw('journal_id, SUM(debit_cdf) as debit, SUM(credit_cdf) as credit')
            ->groupBy('journal_id');
        $base = DB::table('ecritures_comptables as e')
            ->join('journaux as j', 'j.id', '=', 'e.journal_id')
            ->join('journal_types as source', 'source.id', '=', 'j.journal_type_id')
            ->joinSub($totals, 'totals', fn ($join) => $join->on('totals.journal_id', '=', 'j.id'))
            ->join('journal_types as target', function ($join) {
                $join->on('target.liste_des_comptes_id', '=', 'e.liste_des_comptes_id')
                    ->where('target.est_tresorerie', true);
            })
            ->whereRaw("target.id = COALESCE(
                (SELECT MIN(c.id) FROM journal_types c WHERE c.est_tresorerie = 1
                 AND c.liste_des_comptes_id = e.liste_des_comptes_id AND c.monnaie = j.monnaie),
                (SELECT MIN(c.id) FROM journal_types c WHERE c.est_tresorerie = 1
                 AND c.liste_des_comptes_id = e.liste_des_comptes_id))")
            ->whereNull('e.deleted_at')->whereNull('j.deleted_at')
            ->where('e.statut', 'Validé')->where('j.statut', 'Validé')
            // The source treasury account is already counted by ordinary journals.
            ->where(fn ($q) => $q->where('source.est_tresorerie', false)
                ->orWhereColumn('e.liste_des_comptes_id', '<>', 'source.liste_des_comptes_id'))
            // BRCs have their own original-currency projection above. Include
            // deleted BRC links in this exclusion, as well as closing summaries.
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('brcs')->whereColumn('brcs.journal_id', 'j.id'))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('brc_journal')->whereColumn('brc_journal.journal_id', 'j.id'));

        $originalUsd = 'CASE WHEN j.montant_ttc > 0 THEN j.montant_ttc ELSE COALESCE(j.entrees_usd, 0) + COALESCE(j.sorties_usd, 0) END';
        $totalCdf = 'NULLIF(CASE WHEN totals.debit >= totals.credit THEN totals.debit ELSE totals.credit END, 0)';
        return $base->selectRaw("j.id, j.user_id, target.id as journal_type_id, e.liste_des_comptes_id,
            j.reference, e.date, e.libelle as description, j.type, j.monnaie, e.statut,
            j.entree_caisse_id, j.sortie_caisse_id, j.deleted_at,
            CASE WHEN j.monnaie = 'USD' THEN 0 ELSE e.debit_cdf END as entrees_cdf,
            CASE WHEN j.monnaie = 'USD' THEN 0 ELSE e.credit_cdf END as sorties_cdf,
            CASE WHEN j.monnaie = 'USD' THEN ROUND(e.debit_cdf * 1.0 * ($originalUsd) / $totalCdf, 2) ELSE 0 END as entrees_usd,
            CASE WHEN j.monnaie = 'USD' THEN ROUND(e.credit_cdf * 1.0 * ($originalUsd) / $totalCdf, 2) ELSE 0 END as sorties_usd");
    }
}
