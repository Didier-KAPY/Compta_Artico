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
    public function getData(User $user): array
    {
        $role = mb_strtolower(trim((string) $user->role?->designation));
        $sections = $this->sectionsFor($role);

        return $this->buildData($sections) + ['sections' => $sections];
    }

    private function buildData(array $sections): array
    {
        $data = [];

        if ($sections['statistics']) {
            $data['statistics'] = [
                'users' => User::count(),
                'brc' => BRC::count(),
                'cash_in' => EntreeCaisse::count(),
                'cash_out' => SortieCaisse::count(),
                'needs' => EtatBesoin::count(),
                'accounts' => ListeDesComptes::count(),
            ];
        }

        if ($sections['cash']) {
            $totals = Journaux::query()->selectRaw(
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
            $positions = Journaux::query()
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
        $cashier = in_array($role, ['caissier', 'caissière', 'trésorier', 'trésorière'], true);
        $accounting = in_array($role, [
            'daf', 'comptable', 'chargé des finances',
            'chargé de finance', 'charge de finance', 'charger de finance',
        ], true);
        $department = in_array($role, ['chef de département', 'chef de service'], true);
        $management = $admin || $department;

        return [
            'statistics' => $admin || $department || $accounting,
            'cash' => $admin || $cashier,
            'treasury_situation' => $management,
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
