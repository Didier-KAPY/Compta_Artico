<?php

namespace App\Services;

use App\Models\BRC;
use App\Models\EcritureComptable;
use App\Models\EntreeCaisse;
use App\Models\EtatBesoin;
use App\Models\Journaux;
use App\Models\SortieCaisse;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProfileNotificationService
{
    public function forUser(User $user): array
    {
        $canSeeAll = $user->isSuperAdmin() || $user->isManagement() || $user->isAccounting();
        $modules = collect();
        $items = collect();

        $addModule = function (
            string $key,
            string $label,
            string $icon,
            string $url,
            Builder $query,
            callable $title,
            callable $description
        ) use ($modules, $items, $user, $canSeeAll): void {
            if (! $canSeeAll && $query->getModel()->isFillable('user_id')) {
                $query->where('user_id', $user->id);
            }

            $enAttente = (clone $query)->where('statut', 'En attente')->count();
            $valides = (clone $query)->where('statut', 'Validé')->count();

            if ($enAttente === 0 && $valides === 0) {
                return;
            }

            $modules->push([
                'key' => $key,
                'label' => $label,
                'icon' => $icon,
                'url' => $url,
                'en_attente' => $enAttente,
                'valides' => $valides,
            ]);

            (clone $query)
                ->whereIn('statut', ['En attente', 'Validé'])
                ->latest('updated_at')
                ->limit(10)
                ->get()
                ->each(function ($record) use ($items, $key, $label, $icon, $url, $title, $description): void {
                    $items->push([
                        'module' => $key,
                        'module_label' => $label,
                        'icon' => $icon,
                        'url' => $url,
                        'title' => $title($record),
                        'description' => $description($record),
                        'statut' => $record->statut,
                        'date' => $record->updated_at,
                    ]);
                });
        };

        if ($user->can('viewJournalIndex')) {
            $addModule('journaux', 'Journaux', 'bi-journal-text', route('journaux.index'), Journaux::query(),
                fn (Journaux $journal): string => $journal->reference ?: 'Journal #'.$journal->id,
                fn (Journaux $journal): string => $journal->description ?: 'Opération comptable');
        }

        if ($user->hasRole(['Super Admin', 'Admin', 'Directeur Général', 'DAF', 'Comptable'])) {
            $addModule('ecritures', 'Écritures comptables', 'bi-calculator', route('ecritures.liste'), EcritureComptable::query(),
                fn (EcritureComptable $ecriture): string => $ecriture->piece ?: 'Écriture #'.$ecriture->id,
                fn (EcritureComptable $ecriture): string => $ecriture->libelle ?: 'Écriture comptable');
        }

        $addModule('besoins', 'États de besoin', 'bi-clipboard-check', route('etat-besoins.index'), EtatBesoin::query(),
            fn (EtatBesoin $etat): string => $etat->numero ?: 'État de besoin #'.$etat->id,
            fn (EtatBesoin $etat): string => $etat->motif ?: ($etat->service ?: 'Demande de besoin'));

        if ($user->hasRole(['Super Admin', 'Admin', 'Directeur Général', 'DAF', 'Comptable', 'Caissier', 'Caissière', 'Trésorier', 'Trésorière'])) {
            $addModule('entrees', 'Entrées de caisse', 'bi-box-arrow-in-down', route('entree-caisses.index'), EntreeCaisse::query(),
                fn (EntreeCaisse $entree): string => $entree->numero ?: 'Entrée #'.$entree->id,
                fn (EntreeCaisse $entree): string => $entree->motif ?: 'Entrée de caisse');

            $addModule('sorties', 'Sorties de caisse', 'bi-box-arrow-up', route('sortie-caisses.index'), SortieCaisse::query(),
                fn (SortieCaisse $sortie): string => $sortie->numero ?: 'Sortie #'.$sortie->id,
                fn (SortieCaisse $sortie): string => $sortie->motif ?: ($sortie->beneficiaire ?: 'Sortie de caisse'));
        }

        if ($user->hasRole(['Super Admin', 'Admin', 'Directeur Général', 'DAF', 'Comptable'])) {
            $addModule('brc', 'BRC', 'bi-receipt', route('brc.index'), BRC::query(),
                fn (BRC $brc): string => $brc->reference ?: 'BRC #'.$brc->id,
                fn (BRC $brc): string => 'Bon de recette comptable');
        }

        return [
            'en_attente' => $modules->sum('en_attente'),
            'valides' => $modules->sum('valides'),
            'modules' => $modules->values(),
            'items' => $items->sortByDesc('date')->take(30)->values(),
        ];
    }
}