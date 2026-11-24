<?php

namespace App\Services;

use App\Models\BRC;
use App\Models\EcritureComptable;
use App\Models\EntreeCaisse;
use App\Models\EtatBesoin;
use App\Models\Journaux;
use App\Models\JournalType;
use App\Models\ListeDesComptes;
use App\Models\ParametrageComptable;
use App\Models\SortieCaisse;
use App\Models\TauxDeChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getCashierData(?\Carbon\CarbonImmutable $month = null): array
    {
        $month = ($month ?? \Carbon\CarbonImmutable::now())->startOfMonth();
        $start = $month->toDateString();
        $end = $month->endOfMonth()->toDateString();

        $entries = EntreeCaisse::query()->whereBetween('date', [$start, $end]);
        $outputs = SortieCaisse::query()->whereBetween('date', [$start, $end]);
        $pendingEntries = EntreeCaisse::query()->where('statut', 'En attente');
        $pendingOutputs = SortieCaisse::query()->where('statut', 'En attente');

        return [
            'entry_count' => (clone $entries)->count(),
            'entry_totals' => (clone $entries)->select('monnaie')->selectRaw('COALESCE(SUM(montant), 0) AS total')->groupBy('monnaie')->pluck('total', 'monnaie'),
            'output_count' => (clone $outputs)->count(),
            'output_totals' => (clone $outputs)->select('monnaie')->selectRaw('COALESCE(SUM(montant), 0) AS total')->groupBy('monnaie')->pluck('total', 'monnaie'),
            'pending_entries' => $pendingEntries->count(),
            'pending_outputs' => $pendingOutputs->count(),
            'treasury_situation' => $this->getTreasurySituation(),
        ];
    }

    public function getData(User $user, ?\Carbon\CarbonImmutable $month = null): array
    {
        $role = mb_strtolower(trim((string) $user->role?->designation));
        $sections = $this->sectionsFor($role);
        $data = $this->buildData($sections, ($month ?? \Carbon\CarbonImmutable::now())->startOfMonth());

        $asciiRole = \Illuminate\Support\Str::ascii($role);
        if (in_array($asciiRole, ['super admin', 'gerant', 'directeur general'], true) || in_array($role, ['super admin', 'gérant', 'gerant', 'gÃ©rant', 'directeur général', 'directeur gÃ©nÃ©ral'], true)) {
            $setting = ParametrageComptable::with('compte')->where('code', 'DASHBOARD_GERANT_TVA_DUE')->first();
            if ($setting?->compte) {
                $accountMonth = ($month ?? \Carbon\CarbonImmutable::now())->startOfMonth();
                $entry = EcritureComptable::query()
                    ->where('liste_des_comptes_id', $setting->liste_des_comptes_id)
                    ->where('statut', "Valid\u{00C3}\u{00A9}")
                    ->whereBetween('date', [$accountMonth->toDateString(), $accountMonth->endOfMonth()->endOfDay()->toDateTimeString()])
                    ->latest('date')->latest('id')
                    ->first(['debit_cdf', 'credit_cdf']);
                $debit = (float) ($entry?->debit_cdf ?? 0);
                $credit = (float) ($entry?->credit_cdf ?? 0);
                $amountSide = $entry ? (abs($debit) > 0 ? 'Débit enregistré' : 'Crédit enregistré') : null;
                $recordedAmount = $entry ? (abs($debit) > 0 ? $debit : $credit) : null;
                $data['management_account'] = [
                    'account' => $setting->compte,
                    'amount' => $recordedAmount,
                    'amount_side' => $amountSide,
                ];
            }
        }

        return $data + ['sections' => $sections];
    }

    private function buildData(array $sections, \Carbon\CarbonImmutable $month): array
    {
        $data = [];
        $debutMois = $month->toDateString();
        $finMois = $month->endOfMonth()->endOfDay()->toDateTimeString();

        if ($sections['statistics']) {
            $data['statistics'] = [
                'users' => User::count(),
                'brc' => BRC::whereBetween('date', [$debutMois, $finMois])->count(),
                'cash_in' => EntreeCaisse::whereBetween('date', [$debutMois, $finMois])->count(),
                'cash_out' => SortieCaisse::whereBetween('date', [$debutMois, $finMois])->count(),
                'needs' => EtatBesoin::whereBetween('date', [$debutMois, $finMois])->count(),
                'accounts' => ListeDesComptes::count(),
            ];
        }

        if ($sections['cash']) {
            $totals = app(\App\Services\TreasuryMovementService::class)->query()
                ->where('statut', 'Validé')
                ->whereHas('journalType', fn ($query) => $query->where('est_tresorerie', true))
                ->whereDate('date', '<=', now()->toDateString())
                ->selectRaw(
                    'COALESCE(SUM(entrees_cdf), 0) AS in_cdf,
                 COALESCE(SUM(sorties_cdf), 0) AS out_cdf,
                 COALESCE(SUM(entrees_usd), 0) AS in_usd,
                 COALESCE(SUM(sorties_usd), 0) AS out_usd'
                )->first();
            $data['cash'] = [
                'in_cdf' => (float) $totals->in_cdf,
                'out_cdf' => (float) $totals->out_cdf,
                'balance_cdf' => (float) $totals->in_cdf - (float) $totals->out_cdf,
                'in_usd' => (float) $totals->in_usd,
                'out_usd' => (float) $totals->out_usd,
                'balance_usd' => (float) $totals->in_usd - (float) $totals->out_usd,
            ];
        }

        if ($sections['treasury_situation']) {
            $data['treasury_situation'] = $this->getTreasurySituation();
        }

        if ($sections['charts']) {
            $monthly = Journaux::query()
                ->selectRaw($this->monthExpression().' AS month')
                ->selectRaw('SUM(entrees_cdf) AS in_cdf, SUM(sorties_cdf) AS out_cdf')
                ->selectRaw('SUM(entrees_usd) AS in_usd, SUM(sorties_usd) AS out_usd')
                ->whereYear('date', now()->year)
                ->groupBy(DB::raw($this->monthExpression()))
                ->get()->keyBy('month');
            $operations = Journaux::query()
                ->select('type')->selectRaw('SUM(montant_ttc) AS total')
                ->whereIn('type', ['recette', 'achat', 'depense', 'vente'])
                ->groupBy('type')->pluck('total', 'type');
            $payments = Journaux::query()
                ->select('mode_paiement')->selectRaw('COUNT(*) AS total')
                ->groupBy('mode_paiement')->pluck('total', 'mode_paiement');

            $data['charts'] = [
                'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
                'in_cdf' => $this->months($monthly, 'in_cdf'),
                'out_cdf' => $this->months($monthly, 'out_cdf'),
                'in_usd' => $this->months($monthly, 'in_usd'),
                'out_usd' => $this->months($monthly, 'out_usd'),
                'treasury' => array_map(fn ($month): float => (float) (($month?->in_cdf ?? 0) - ($month?->out_cdf ?? 0)), array_values($monthly->all())),
                'operations' => collect(['recette', 'achat', 'depense', 'vente'])->map(fn ($type) => (float) ($operations[$type] ?? 0))->all(),
                'payments' => [
                    (int) ($payments['espèces'] ?? 0),
                    (int) ($payments['banque'] ?? 0),
                    (int) ($payments['mobile_money'] ?? 0),
                ],
            ];
            $data['charts']['treasury'] = $this->runningBalance($data['charts']['in_cdf'], $data['charts']['out_cdf']);
        }

        if ($sections['validations']) {
            $data['validations'] = [
                'brc' => BRC::where('statut', 'En attente')->count(),
                'cash_in' => EntreeCaisse::where('statut', 'En attente')->count(),
                'needs' => EtatBesoin::where('statut', 'En attente')->count(),
                'cash_out' => SortieCaisse::where('statut', 'En attente')->count(),
                'entries' => EcritureComptable::where('statut', 'En attente')->count(),
                'journals' => Journaux::where('statut', 'En attente')->count(),
            ];
            $data['accounting_alerts'] = [
                'etats_besoin_sans_piece' => EtatBesoin::query()
                    ->where('statut', 'Validé')
                    ->where(fn ($query) => $query->whereNull('piece_justificative')->orWhere('piece_justificative', ''))
                    ->where(fn ($query) => $query->whereNull('pieces_justificatives')->orWhereJsonLength('pieces_justificatives', 0))
                    ->count(),
            ];
        }

        if ($sections['operations']) {
            $data['latest_operations'] = Journaux::query()
                ->with('validateur:id,nom,prenom')
                ->latest('date')->latest('id')->limit(10)->get();
        }

        if ($sections['exchange']) {
            $data['exchange_rate'] = TauxDeChange::query()->latest('updated_at')->first();
        }

        return $data;
    }

    private function sectionsFor(string $role): array
    {
        $admin = in_array($role, ['super admin', 'admin', 'directeur général', 'gérant', 'gerant'], true);
        $chargeFinances = in_array($role, ['chargé des finances', 'chargé de finance', 'charge de finance', 'charger de finance'], true);
        $cashier = in_array($role, ['caissier', 'caissière', 'trésorier', 'trésorière'], true);
        $accounting = in_array($role, [
            'daf', 'comptable', 'chargé des finances',
            'chargé de finance', 'charge de finance', 'charger de finance',
        ], true);
        $department = in_array($role, ['chef de département', 'chef de service'], true);
        $management = $admin || $department;

        return [
            'statistics' => $admin || $department || $accounting,
            'cash' => $admin || $cashier || $chargeFinances,
            'treasury_situation' => $management || $chargeFinances,
            'charts' => $admin || $cashier || $accounting,
            'validations' => $admin || $cashier || $accounting || $department,
            'operations' => $admin || $cashier || $accounting,
            'exchange' => $admin || $cashier || $accounting,
            'shortcuts' => true,
            'needs_only' => $department,
        ];
    }

    private function monthExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', date) AS INTEGER)"
            : 'MONTH(date)';
    }

    private function getTreasurySituation(): array
    {
        $positions = app(\App\Services\TreasuryMovementService::class)->query()
            ->select('journal_type_id')
            ->selectRaw('COALESCE(SUM(entrees_cdf), 0) AS entree_cdf')
            ->selectRaw('COALESCE(SUM(sorties_cdf), 0) AS sortie_cdf')
            ->selectRaw('COALESCE(SUM(entrees_usd), 0) AS entree_usd')
            ->selectRaw('COALESCE(SUM(sorties_usd), 0) AS sortie_usd')
            ->with('journalType.compte')
            ->where('statut', 'ValidÃ©')
            ->whereHas('journalType', fn ($query) => $query->where('est_tresorerie', true))
            ->whereDate('date', '<=', now()->toDateString())
            ->groupBy('journal_type_id')
            ->get();

        $accounts = $positions->map(fn ($position): array => [
            'code' => $position->journalType?->code ?? '—',
            'account' => $position->journalType?->compte?->compte ?? '—',
            'designation' => $position->journalType?->compte?->designation
                ?? $position->journalType?->libelle
                ?? 'Compte de trésorerie',
            'nature' => $position->journalType?->nature ?? 'autre',
            'balance_cdf' => (float) $position->entree_cdf - (float) $position->sortie_cdf,
            'balance_usd' => (float) $position->entree_usd - (float) $position->sortie_usd,
        ])->sortBy('code')->values();

        $totals = [];
        foreach (['caisse' => 'caisse', 'banque' => 'banque', 'mobile' => 'mobile_money'] as $key => $nature) {
            $lines = $accounts->where('nature', $nature);
            $totals[$key.'_cdf'] = (float) $lines->sum('balance_cdf');
            $totals[$key.'_usd'] = (float) $lines->sum('balance_usd');
        }
        $totals['total_cdf'] = (float) $accounts->sum('balance_cdf');
        $totals['total_usd'] = (float) $accounts->sum('balance_usd');

        return compact('accounts', 'totals');
    }

    private function months($monthly, string $field): array
    {
        return collect(range(1, 12))->map(fn ($month): float => (float) ($monthly->get($month)?->{$field} ?? 0))->all();
    }

    private function runningBalance(array $entries, array $outputs): array
    {
        $balance = 0;

        return collect($entries)->map(function ($entry, $index) use ($outputs, &$balance): float {
            $balance += (float) $entry - (float) $outputs[$index];
            return $balance;
        })->all();
    }
}
