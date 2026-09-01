<?php

namespace App\Services;

use App\Models\RhPaie;
use Illuminate\Support\Facades\DB;

class RhPaymentReferenceService
{
    public function generate(int $entrepriseId, int $annee, int $mois): string
    {
        return DB::transaction(function () use ($entrepriseId, $annee, $mois): string {
            $prefixe = sprintf('PAY-%04d%02d-', $annee, $mois);
            $derniere = RhPaie::withTrashed()
                ->where('entreprise_id', $entrepriseId)
                ->where('reference_paiement', 'like', $prefixe.'%')
                ->lockForUpdate()
                ->orderByDesc('reference_paiement')
                ->value('reference_paiement');

            $numero = $derniere ? ((int) substr($derniere, -6)) + 1 : 1;

            do {
                $reference = $prefixe.str_pad((string) $numero++, 6, '0', STR_PAD_LEFT);
            } while (RhPaie::withTrashed()->where('entreprise_id', $entrepriseId)
                ->where('reference_paiement', $reference)->exists());

            return $reference;
        });
    }
}
