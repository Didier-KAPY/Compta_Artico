<?php

namespace App\Services;

use App\Models\ClotureJournaliere;
use App\Models\PeriodeComptable;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PeriodeComptableService
{
    public function assertOpen(string|CarbonInterface $date): void
    {
        $date = Carbon::parse($date)->toDateString();
        $periode = PeriodeComptable::where('statut', 'fermee')
            ->whereDate('date_debut', '<=', $date)->whereDate('date_fin', '>=', $date)->first();

        if ($periode) {
            throw ValidationException::withMessages([
                'date' => "La date appartient à une période {$periode->type} clôturée ({$periode->date_debut->format('d/m/Y')} – {$periode->date_fin->format('d/m/Y')}).",
            ]);
        }

        if (ClotureJournaliere::whereDate('date_comptable', $date)->where('statut', '!=', 'reouverte')->exists()) {
            throw ValidationException::withMessages(['date' => 'Cette journée comptable est clôturée. Réouvrez-la avant toute nouvelle opération.']);
        }
    }
}
