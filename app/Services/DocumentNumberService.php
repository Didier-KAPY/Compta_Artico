<?php

namespace App\Services;

use App\Models\Entreprise;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentNumberService
{
    public function next(
        string $typeDocument,
        CarbonInterface|string $dateComptable,
        ?string $typeTresorerie = null,
        ?int $entrepriseId = null
    ): string {
        $date = $dateComptable instanceof CarbonInterface
            ? $dateComptable
            : Carbon::parse($dateComptable);
        $type = strtoupper(trim($typeDocument));
        $tresorerie = $this->normaliserTresorerie($typeTresorerie);
        if ($type === 'BSC') {
            $type = match ($tresorerie) {
                'banque' => 'BSB',
                'mobile_money' => 'BSM',
                default => 'BSC',
            };
        }
        $entrepriseId ??= (int) (Entreprise::query()->value('id') ?? 0);

        if (! in_array($type, ['BEC', 'BEM', 'BEB', 'BSC', 'BSB', 'BSM', 'BRC', 'EB', 'CLJ'], true)) {
            throw new InvalidArgumentException("Type de document non pris en charge : {$type}");
        }

        return DB::transaction(function () use ($type, $date, $tresorerie, $entrepriseId): string {
            $scope = [
                'entreprise_id' => $entrepriseId,
                'type_document' => $type,
                'type_tresorerie' => $tresorerie,
                'annee' => $date->year,
                'mois' => $date->month,
            ];

            DB::table('document_sequences')->insertOrIgnore($scope + [
                'dernier_numero' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('document_sequences')
                ->where($scope)
                ->lockForUpdate()
                ->first();
            // Une installation peut déjà contenir des documents créés avant la
            // table de séquences. On ne doit jamais repartir de 1 ni réutiliser
            // une référence supprimée logiquement.
            $numero = max((int) $sequence->dernier_numero, $this->dernierNumeroExistant($type, $date, $tresorerie)) + 1;

            DB::table('document_sequences')->where('id', $sequence->id)->update([
                'dernier_numero' => $numero,
                'updated_at' => now(),
            ]);

            return $this->formater($type, $date, $numero, $tresorerie);
        }, 5);
    }

    private function formater(string $type, CarbonInterface $date, int $numero, string $tresorerie): string
    {
        $codeTresorerie = match ($tresorerie) {
            'banque' => '-B',
            'mobile_money' => '-M',
            default => '',
        };

        return match ($type) {
            'BEC', 'BEM', 'BEB' => $type.'-'.$date->format('ym').str_pad((string) $numero, 4, '0', STR_PAD_LEFT),
            'BSC', 'BSB', 'BSM' => $type.'-'.$date->format('ym').str_pad((string) $numero, 4, '0', STR_PAD_LEFT),
            'BRC' => 'BRC-'.$date->format('Ymd').'-'.str_pad((string) $numero, 6, '0', STR_PAD_LEFT),
            'EB' => 'EB-'.str_pad((string) $numero, 4, '0', STR_PAD_LEFT).'-'.$date->format('y-m'),
            'CLJ' => 'CLJ-'.$date->format('y-m').'-'.str_pad((string) $numero, 4, '0', STR_PAD_LEFT),
        };
    }

    private function normaliserTresorerie(?string $type): string
    {
        return match (mb_strtolower(trim((string) $type))) {
            'caisse' => 'caisse',
            'banque' => 'banque',
            'mobile money', 'monnaie électronique', 'monnaie electronique', 'mobile_money' => 'mobile_money',
            default => '',
        };
    }

    private function dernierNumeroExistant(string $type, CarbonInterface $date, string $tresorerie): int
    {
        [$table, $colonne, $motif] = match ($type) {
            'EB' => ['etat_besoins', 'numero', '/^EB-(\d+)-'.$date->format('y-m').'$/'],
            'BEC', 'BEM', 'BEB' => ['entree_caisses', 'numero', $this->motifBon($type, $date, '')],
            'BSC', 'BSB', 'BSM' => ['sortie_caisses', 'numero', $this->motifBon($type, $date, '')],
            'BRC' => ['brcs', 'reference', '/^BRC-'.$date->format('Ym').'\d{2}-(\d+)$/'],
            'CLJ' => ['clotures_journalieres', 'numero_cloture', '/^CLJ-'.$date->format('y-m').'-(\d+)$/'],
        };

        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return 0;
        }

        return DB::table($table)->pluck($colonne)->reduce(function (int $maximum, $reference) use ($motif): int {
            return preg_match($motif, (string) $reference, $matches)
                ? max($maximum, (int) $matches[1])
                : $maximum;
        }, 0);
    }

    private function motifBon(string $type, CarbonInterface $date, string $tresorerie): string
    {
        $code = match ($tresorerie) {
            'banque' => '-B',
            'mobile_money' => '-M',
            default => '',
        };

        return '/^'.$type.$code.'-'.$date->format('ym').'(\d{4,})$/';
    }
}
