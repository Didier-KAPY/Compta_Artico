<?php

namespace App\Services;

use App\Models\BRC;
use App\Models\EcritureComptable;
use App\Models\EntreeCaisse;
use App\Models\EtatBesoin;
use App\Models\Journaux;
use App\Models\JournalType;
use App\Models\ListeDesComptes;
use App\Models\SortieCaisse;
use App\Models\TauxDeChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getData(User $user, ?\Carbon\CarbonImmutable $month = null): array
    {
        $role = mb_strtolower(trim((string) $user->role?->designation));
        $sections = $this->sectionsFor($role);

        return $this->buildData($sections, ($month ?? \Carbon\CarbonImmutable::now())->startOfMonth()) + ['sections' => $sections];
    }

    private function buildData(array $sections, \Carbon\CarbonImmutable $month): array
    {
        $data = [];
        $debutMois = $month->toDateString();
        $finMois = $month->endOfMonth()->endOfDay()->toDateTimeString();

        if ($sections['statistics']) {
            $data['statistics'] = [
                'users' => User::whereBetween('created_at', [$month, $month->endOfMonth()->endOfDay()])->count(),
                'brc' => BRC::whereBetween('date', [$debutMois, $finMois])->count(),
                'cash_in' => EntreeCaisse::whereBetween('date', [$debutMois, $finMois])->count(),
                'cash_out' => SortieCaisse::whereBetween('date', [$debutMois, $finMois])->count(),
                'needs' => EtatBesoin::whereBetween('date', [$debutMois, $finMois])->count(),
                'accounts' => ListeDesComptes::whereBetween('created_at', [$month, $month->endOfMonth()->endOfDay()])->count(),
            ];
        }

        if ($sections['cash']) {
            $totals = app(\App\Services\TreasuryMovementService::class)->query()
                ->whereBetween('date', [$debutMois, $finMois])
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
            $positions = app(\App\Services\TreasuryMovementService::class)->query()
                ->whereBetween('date', [$debutMois, $finMois])
                ->select('journal_type_id')
                ->selectRaw('COALESCE(SUM(entrees_cdf), 0) AS entree_cdf')
                ->selectRaw('COALESCE(SUM(sorties_cdf), 0) AS sortie_cdf')
                ->selectRaw('COALESCE(SUM(entrees_usd), 0) AS entree_usd')
                ->selectRaw('COALESCE(SUM(sorties_usd), 0) AS sortie_usd')
                ->with('journalType.compte')
                ->where('statut', 'Validé')
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

            $data['treasury_situation'] = compact('accounts', 'totals');
        }

        if ($sections['charts']) {
            $daily = Journaux::query()
                ->selectRaw($this->dayExpression().' AS day')
                ->selectRaw('SUM(entrees_cdf) AS in_cdf, SUM(sorties_cdf) AS out_cdf')
                ->selectRaw('SUM(entrees_usd) AS in_usd, SUM(sorties_usd) AS out_usd')
                ->whereBetween('date', [$debutMois, $finMois])
                ->groupBy(DB::raw($this->dayExpression()))
                ->get()->keyBy('day');
            $operations = Journaux::query()
                ->whereBetween('date', [$debutMois, $finMois])
                ->select('type')->selectRaw('SUM(montant_ttc) AS total')
                ->whereIn('type', ['recette', 'achat', 'depense', 'vente'])
                ->groupBy('type')->pluck('total', 'type');
            $payments = Journaux::query()
                ->whereBetween('date', [$debutMois, $finMois])
                ->select('mode_paiement')->selectRaw('COUNT(*) AS total')
                ->groupBy('mode_paiement')->pluck('total', 'mode_paiement');

            $data['charts'] = [
                'labels' => range(1, $month->daysInMonth),
                'in_cdf' => $this->days($daily, 'in_cdf', $month->daysInMonth),
                'out_cdf' => $this->days($daily, 'out_cdf', $month->daysInMonth),
                'in_usd' => $this->days($daily, 'in_usd', $month->daysInMonth),
                'out_usd' => $this->days($daily, 'out_usd', $month->daysInMonth),
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
                'brc' => BRC::whereBetween('date', [$debutMois, $finMois])->where('statut', 'En attente')->count(),
                'cash_in' => EntreeCaisse::whereBetween('date', [$debutMois, $finMois])->where('statut', 'En attente')->count(),
                'needs' => EtatBesoin::whereBetween('date', [$debutMois, $finMois])->where('statut', 'En attente')->count(),
                'cash_out' => SortieCaisse::whereBetween('date', [$debutMois, $finMois])->where('statut', 'En attente')->count(),
                'entries' => EcritureComptable::whereBetween('date', [$debutMois, $finMois])->where('statut', 'En attente')->count(),
                'journals' => Journaux::whereBetween('date', [$debutMois, $finMois])->where('statut', 'En attente')->count(),
            ];
            $data['accounting_alerts'] = [
                'etats_besoin_sans_piece' => EtatBesoin::query()
                    ->whereBetween('date', [$debutMois, $finMois])
                    ->where('statut', 'Validé')
                    ->where(fn ($query) => $query->whereNull('piece_justificative')->orWhere('piece_justificative', ''))
                    ->where(fn ($query) => $query->whereNull('pieces_justificatives')->orWhereJsonLength('pieces_justificatives', 0))
                    ->count(),
            ];
        }

        if ($sections['operations']) {
            $data['latest_operations'] = Journaux::query()
                ->whereBetween('date', [$debutMois, $finMois])
                ->with('validateur:id,nom,prenom')
                ->latest('date')->latest('id')->limit(10)->get();
        }

        if ($sections['exchange']) {
            $data['exchange_rate'] = TauxDeChange::query()->whereBetween('updated_at', [$month, $month->endOfMonth()->endOfDay()])->latest('updated_at')->first();
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

    private function dayExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%d', date) AS INTEGER)"
            : 'DAY(date)';
    }

    private function days($daily, string $field, int $days): array
    {
        return collect(range(1, $days))->map(fn ($day): float => (float) ($daily->get($day)?->{$field} ?? 0))->all();
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
