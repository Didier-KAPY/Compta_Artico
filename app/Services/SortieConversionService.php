<?php

namespace App\Services;

use App\Models\SortieCaisse;
use App\Models\TauxDeChange;
use Illuminate\Validation\ValidationException;

class SortieConversionService
{
    public function montantComptableOrigine(SortieCaisse $sortie): ?string
    {
        if (! $sortie->taux_conversion || $sortie->montant_origine_conversion === null) return null;

        $montant = \Brick\Math\BigDecimal::of($sortie->montant_origine_conversion);
        if ($sortie->monnaie_origine_conversion === 'USD') {
            $montant = $montant->multipliedBy($sortie->taux_conversion);
        }
        return (string) $montant;
    }

    public function convertir(string $montant, string $monnaie, TauxDeChange $taux): string
    {
        $decimal = \Brick\Math\BigDecimal::of($montant);
        return (string) ($monnaie === 'USD'
            ? $decimal->multipliedBy($taux->taux_de_change)
            : $decimal->dividedBy($taux->taux_de_change, 18, \Brick\Math\RoundingMode::DOWN));
    }

    public function taux(SortieCaisse $sortie): ?TauxDeChange
    {
        $entrepriseId = auth()->user()?->entreprises()->orderBy('id')->first()?->id
            ?? \App\Models\Entreprise::orderBy('id')->value('id');
        return TauxDeChange::where('devise_source', 'USD')->where('devise_cible', 'CDF')
            ->where(fn ($q) => $q->whereNull('entreprise_id')->orWhere('entreprise_id', $entrepriseId))
            ->where(fn ($q) => $q->whereDate('date_taux', '<=', today())
                ->orWhere(fn ($legacy) => $legacy->whereNull('date_taux')->whereDate('created_at', '<=', today())))
            ->orderByRaw('COALESCE(date_taux, created_at) DESC')->orderByDesc('id')->first();
    }

    public function appliquer(SortieCaisse $sortie, bool $convertir): void
    {
        $etat = $sortie->etatBesoin;
        if (! $etat || $sortie->origine === 'cloture') {
            if ($convertir) throw ValidationException::withMessages(['conversion' => 'La conversion nécessite un état de besoins lié.']);
            return;
        }
        $montant = (float) $etat->montant_estime;
        $monnaie = $etat->monnaie;
        $taux = $convertir ? $this->taux($sortie) : null;
        if ($convertir) {
            if (! $taux || (float) $taux->taux_de_change <= 0) {
                throw ValidationException::withMessages(['conversion' => 'Aucun taux de change valide n’est disponible aujourd’hui.']);
            }
            $montant = $monnaie === 'USD' ? $montant * (float) $taux->taux_de_change : $montant / (float) $taux->taux_de_change;
            $monnaie = $monnaie === 'USD' ? 'CDF' : 'USD';
        }
        $montant = round($montant, 2);
        if ($montant <= 0) throw ValidationException::withMessages(['conversion' => 'Le montant à transmettre doit être supérieur à zéro.']);
        $ht = $sortie->appliquer_tva ? round($montant / (1 + (float) $sortie->taux_tva / 100), 2) : $montant;
        $sortie->fill([
            'montant' => $montant, 'monnaie' => $monnaie,
            'montant_ht' => $ht, 'montant_tva' => round($montant - $ht, 2),
            'taux_conversion' => $taux?->taux_de_change,
            'date_taux_conversion' => $taux ? ($taux->date_taux ?? $taux->created_at)->toDateString() : null,
        ])->save();
    }
}
