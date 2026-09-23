<?php

namespace App\Services;

use App\Models\{ConstatationComptable, EcritureComptable, Entreprise, Journaux, ListeDesComptes, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConstatationComptableService
{
    public function companyId($model): ?int
    {
        if ($model->entreprise_id) return (int) $model->entreprise_id;
        $owned = Entreprise::where('user_id', $model->user_id)->pluck('id');
        if ($owned->count() === 1) return (int) $owned->first();
        return Entreprise::count() === 1 ? (int) Entreprise::value('id') : null;
    }

    public function assertCompany(EcritureComptable $source, User $user): int
    {
        $company = app(CurrentEntreprise::class)->for($user)->id;
        abort_unless($this->companyId($source) === $company && $source->journal && $this->companyId($source->journal) === $company, 404);
        return $company;
    }

    public function eligible(EcritureComptable $source): bool
    {
        $journal = $source->journal;
        if ($source->date) {
            try {
                app(PeriodeComptableService::class)->assertOpen($source->date);
            } catch (ValidationException) {
                return false;
            }
        }
        return $source->statut === 'En attente' && !$source->constatation_id && $journal
            && mb_strtolower(trim((string)$journal->statut)) === 'validé'
            && ($journal->entree_caisse_id || $journal->sortie_caisse_id)
            && (int) $source->liste_des_comptes_id === (int) $journal->journalType?->liste_des_comptes_id
            && !$journal->constatation && $journal->ecritures()->count() === 1
            && ($this->cents($source->debit_cdf) > 0 xor $this->cents($source->credit_cdf) > 0);
    }

    public function cents($amount): int
    {
        // Work in integer centimes; never balance rounded floating-point sums.
        return (int) round((float) $amount * 100);
    }

    public function snapshot(EcritureComptable $source): array
    {
        $journal = $source->journal;
        $bon = $journal->entreeCaisse ?? $journal->sortieCaisse;
        $cdf = max($this->cents($source->debit_cdf), $this->cents($source->credit_cdf)) / 100;
        $usd = strtoupper((string) $bon?->monnaie) === 'USD' ? (float) ($bon->montant ?? $bon->total ?? 0) : 0;
        return [
            'source_id'=>$source->id, 'piece'=>$source->piece, 'type_piece'=>$bon?->type_bon ?? substr((string)$source->piece, 0, 3),
            'date'=>$source->date->format('Y-m-d'), 'libelle'=>$source->libelle,
            'montant_cdf'=>$cdf, 'montant_usd'=>$usd, 'taux_change'=>$usd > 0 ? $cdf / $usd : null,
            'journal_id'=>$journal->id, 'journal'=>$journal->journalType?->code,
            'createur'=>trim(($source->user?->prenom ?? '').' '.($source->user?->nom ?? '')),
            'statut'=>$source->statut, 'compte_tresorerie'=>$source->compte?->compte,
        ];
    }

    public function store(int $sourceId, array $data, Request $request): ConstatationComptable
    {
        return DB::transaction(function () use ($sourceId, $data, $request) {
            // Lock the journal first: different source IDs of the same payment serialize too.
            $initial = EcritureComptable::findOrFail($sourceId);
            Journaux::whereKey($initial->journal_id)->lockForUpdate()->firstOrFail();
            $source = EcritureComptable::with(['journal.journalType', 'journal.entreeCaisse', 'journal.sortieCaisse', 'compte', 'user'])
                ->lockForUpdate()->findOrFail($sourceId);
            $company = $this->assertCompany($source, $request->user());
            app(PeriodeComptableService::class)->assertOpen($source->date);
            app(PeriodeComptableService::class)->assertOpen($data['date']);
            if (!$this->eligible($source)) $this->fail('Cette opération est déjà constatée, imputée ou ne peut plus être constatée.');
            $accountIds = collect($data['lignes'])->pluck('liste_des_comptes_id')->push($data['compte_liaison_id'])->unique();
            $accounts = ListeDesComptes::whereIn('id', $accountIds)->lockForUpdate()->get();
            if ($accounts->count() !== $accountIds->count() || $accounts->contains(fn($a)=>$this->companyId($a) !== $company)) $this->fail('Un compte ne fait pas partie du plan comptable de l’entreprise active.');
            $link = (int) $data['compte_liaison_id'];
            if ($link === (int)$source->liste_des_comptes_id) $this->fail('Le compte de liaison doit être distinct du compte de trésorerie.');
            $outgoing = $this->cents($source->credit_cdf) > 0;
            if ($data['type_operation'] === 'salaire' && !$outgoing) $this->fail('Une constatation salariale nécessite un bon de sortie.');
            $debit = $credit = $linkNet = $gross = $withholdings = 0;
            foreach ($data['lignes'] as $line) {
                $d = $this->cents($line['debit_cdf']); $c = $this->cents($line['credit_cdf']);
                if (!(($d > 0) xor ($c > 0))) $this->fail('Chaque ligne doit contenir soit un débit positif, soit un crédit positif.');
                if ((int)$line['liste_des_comptes_id'] === (int)$source->liste_des_comptes_id) $this->fail('La constatation ne doit pas recréer un mouvement de trésorerie.');
                $debit += $d; $credit += $c;
                if ((int)$line['liste_des_comptes_id'] === $link) $linkNet += $outgoing ? $c-$d : $d-$c;
                if ($data['type_operation'] === 'salaire') {
                    $code = (string)$accounts->firstWhere('id', $line['liste_des_comptes_id'])->compte;
                    if ($line['nature'] === 'charge') {
                        if ($d <= 0 || !str_starts_with($code, '6') || (int)$line['liste_des_comptes_id'] === $link) $this->fail('Une charge salariale doit être au débit d’un compte de classe 6.');
                        $gross += $d;
                    } elseif ($line['nature'] === 'retenue') {
                        if ($c <= 0 || (int)$line['liste_des_comptes_id'] === $link) $this->fail('Une retenue doit être au crédit, sur un compte distinct de la dette nette.');
                        $withholdings += $c;
                    } elseif ($line['nature'] === 'dette') {
                        if ($c <= 0 || (int)$line['liste_des_comptes_id'] !== $link) $this->fail('La dette nette doit être au crédit du compte de liaison choisi.');
                    } else $this->fail('Pour un salaire, choisissez Charge, Retenue ou Dette nette pour chaque ligne.');
                }
            }
            if ($debit <= 0 || $debit !== $credit) $this->fail('L’écriture n’est pas équilibrée. Veuillez vérifier les montants avant de continuer.');
            $paid = max($this->cents($source->debit_cdf), $this->cents($source->credit_cdf));
            if ($linkNet !== $paid) $this->fail('La dette ou créance nette doit correspondre exactement au montant du règlement.');
            if ($data['type_operation'] === 'salaire' && ($gross <= 0 || $gross-$withholdings !== $paid)) $this->fail('Le salaire brut moins les retenues doit être égal au montant payé.');
            $snapshot = $this->snapshot($source);
            $before = $source->toArray();
            $record = ConstatationComptable::create([
                'entreprise_id'=>$company, 'source_ecriture_id'=>$source->id, 'reglement_journal_id'=>$source->journal_id,
                'compte_liaison_id'=>$link, 'user_id'=>$request->user()->id,
                'numero'=>'CONST-'.$company.'-'.$source->id, 'piece_origine'=>$source->piece ?: $source->journal->reference,
                'type_operation'=>$data['type_operation'], 'date'=>$data['date'], 'montant_cdf'=>$paid/100,
                'montant_usd'=>$snapshot['montant_usd'], 'taux_change'=>$snapshot['taux_change'], 'instantane_source'=>$snapshot,
            ]);
            $common = ['entreprise_id'=>$company, 'user_id'=>$request->user()->id, 'constatation_id'=>$record->id,
                'statut'=>'Validé', 'valide_par'=>$request->user()->id, 'date_validation'=>now()];
            foreach ($data['lignes'] as $line) {
                EcritureComptable::create($common + [
                    'journal_id'=>null, 'piece'=>$record->numero, 'date'=>$data['date'], 'role_constatation'=>'constatation',
                    'nature_constatation'=>$line['nature'], 'liste_des_comptes_id'=>$line['liste_des_comptes_id'],
                    'libelle'=>$line['libelle'], 'debit_cdf'=>$this->cents($line['debit_cdf'])/100, 'credit_cdf'=>$this->cents($line['credit_cdf'])/100,
                ]);
            }
            EcritureComptable::create($common + [
                'journal_id'=>$source->journal_id, 'piece'=>$source->piece, 'date'=>$source->date,
                'role_constatation'=>'reglement', 'liste_des_comptes_id'=>$link,
                'libelle'=>'Règlement — '.$source->libelle, 'debit_cdf'=>$outgoing ? $paid/100 : 0, 'credit_cdf'=>$outgoing ? 0 : $paid/100,
            ]);
            $source->update(['entreprise_id'=>$company, 'constatation_id'=>$record->id, 'role_constatation'=>'reglement',
                'statut'=>'Validé', 'valide_par'=>$request->user()->id, 'date_validation'=>now()]);
            $balance = $record->reglement()->selectRaw('SUM(debit_cdf) debit, SUM(credit_cdf) credit')->first();
            if ($this->cents($balance->debit) !== $this->cents($balance->credit)) $this->fail('Le règlement n’est pas équilibré.');
            app(AuditLogService::class)->record('constatation_comptable', $record, $record->numero, null, $before,
                $record->load('lignes', 'reglement')->toArray(), ['source_id'=>$source->id, 'journal_id'=>$source->journal_id], $request);
            return $record;
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['constatation'=>$message]);
    }
}
