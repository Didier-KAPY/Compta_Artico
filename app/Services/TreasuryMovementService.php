<?php

namespace App\Services;

use App\Models\Journaux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TreasuryMovementService
{
    /** Read-only projection: BRC imputations retain their original currency. */
    public function query(): Builder
    {
        $columns = ['id', 'user_id', 'journal_type_id', 'liste_des_comptes_id',
            'reference', 'date', 'description', 'type', 'monnaie', 'statut',
            'entree_caisse_id', 'sortie_caisse_id', 'deleted_at',
            'entrees_cdf', 'sorties_cdf', 'entrees_usd', 'sorties_usd'];
        $ordinary = DB::table('journaux')->select($columns);

        // The journal's account is debited for sens=debit; imputation lines
        // have the opposite direction. Match both sides to their treasury account.
        $lines = DB::table('ligne_brcs as l')->join('brcs as b', 'b.id', '=', 'l.brc_id')
            ->selectRaw("b.id as brc_id, l.liste_des_comptes_id as compte_id, l.libelle, l.montant,
                CASE WHEN b.sens = 'credit' THEN 'debit' ELSE 'credit' END as sens");
        $head = DB::table('brcs as b')->join('journal_types as jt', 'jt.id', '=', 'b.journal_type_id')
            ->selectRaw('b.id as brc_id, jt.liste_des_comptes_id as compte_id, b.reference as libelle, b.total as montant, b.sens');
        $lines->unionAll($head);

        // A shared account must not multiply the movement if several journal
        // types point at it. Prefer a journal matching the original currency.
        $regularizations = DB::query()->fromSub($lines, 'movement')
            ->join('brcs as b', 'b.id', '=', 'movement.brc_id')
            ->join('journaux as j', 'j.id', '=', 'b.journal_id')
            ->join('journal_types as source', 'source.id', '=', 'j.journal_type_id')
            ->join('journal_types as target', function ($join) {
                $join->on('target.liste_des_comptes_id', '=', 'movement.compte_id')
                    ->where('target.est_tresorerie', true);
            })
            ->whereRaw("target.id = COALESCE((SELECT MIN(candidate.id) FROM journal_types candidate
                WHERE candidate.liste_des_comptes_id = movement.compte_id
                  AND candidate.est_tresorerie = 1
                  AND candidate.monnaie = b.monnaie),
                (SELECT MIN(candidate.id) FROM journal_types candidate
                 WHERE candidate.liste_des_comptes_id = movement.compte_id
                   AND candidate.est_tresorerie = 1))")
            ->whereNull('b.deleted_at')->whereNull('j.deleted_at')
            ->where('b.statut', 'Validé')->where('j.statut', 'Validé')
            ->where(function ($q) { $q->whereNull('b.origine')->orWhere('b.origine', '<>', 'cloture'); })
            // A BRC can also originate in a treasury journal. Its source
            // account is already counted there, but other treasury accounts
            // on the BRC must still receive their own movements.
            ->where(fn ($q) => $q->where('source.est_tresorerie', false)
                ->orWhereColumn('movement.compte_id', '<>', 'source.liste_des_comptes_id'))
            ->selectRaw("j.id, j.user_id, target.id as journal_type_id, movement.compte_id as liste_des_comptes_id,
                b.reference, b.date, movement.libelle as description, j.type, b.monnaie, j.statut,
                j.entree_caisse_id, j.sortie_caisse_id, j.deleted_at,
                CASE WHEN b.monnaie = 'CDF' AND movement.sens = 'debit' THEN movement.montant ELSE 0 END as entrees_cdf,
                CASE WHEN b.monnaie = 'CDF' AND movement.sens = 'credit' THEN movement.montant ELSE 0 END as sorties_cdf,
                CASE WHEN b.monnaie = 'USD' AND movement.sens = 'debit' THEN movement.montant ELSE 0 END as entrees_usd,
                CASE WHEN b.monnaie = 'USD' AND movement.sens = 'credit' THEN movement.montant ELSE 0 END as sorties_usd");

        return Journaux::query()->fromSub(
            $ordinary->unionAll($regularizations)->unionAll($this->accountingCounterparts()),
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
