<?php

namespace App\Services;

use App\Models\BRC;
use App\Models\EcritureComptable;
use App\Models\EntreeCaisse;
use App\Models\EtatBesoin;
use App\Models\Journaux;
use App\Models\SortieCaisse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialDocumentService
{
    public function __construct(private AuditLogService $audit) {}

    public function delete(Model $document, string $motif, string $strategie, Request $request): void
    {
        DB::transaction(function () use ($document, $motif, $strategie, $request) {
            $document = $document->newQuery()->lockForUpdate()->findOrFail($document->getKey());
            $dependances = $this->dependencies($document);

            if ($strategie === 'individuelle' && $dependances !== []) {
                $premiere = $dependances[0];
                throw ValidationException::withMessages([
                    'strategie' => "Suppression individuelle impossible : {$premiere['type']} {$premiere['reference']} ({$premiere['statut']}) dépend de ce document.",
                ]);
            }

            $documents = $strategie === 'cascade'
                ? $this->cascadeDocuments($document)
                : collect([$document]);

            foreach ($documents as $item) {
                $this->softDeleteOne($item, $motif, $strategie, $request);
            }
        });
    }

    public function restore(Model $document, bool $cascade, Request $request): void
    {
        DB::transaction(function () use ($document, $cascade, $request) {
            $document = $document->newQuery()->onlyTrashed()->lockForUpdate()->findOrFail($document->getKey());
            if (! $cascade) {
                $this->assertParentsActive($document);
            }
            $documents = $cascade ? $this->restoreDocuments($document) : collect([$document]);

            foreach ($documents as $item) {
                $this->assertReferenceAvailable($item);
                $before = $item->attributesToArray();
                $item->restore();
                $item->forceFill([
                    'restaure_par' => $request->user()->id,
                    'restaure_le' => now(),
                ])->save();
                $this->audit->record(
                    $cascade ? 'restauration_cascade' : 'restauration',
                    $item,
                    $this->reference($item),
                    null,
                    $before,
                    $item->fresh()->attributesToArray(),
                    $this->dependencies($item, true),
                    $request,
                    $cascade ? 'cascade contrôlée' : 'individuelle',
                );
            }
        });
    }

    public function forceDelete(Model $document, string $motif, Request $request): void
    {
        DB::transaction(function () use ($document, $motif, $request) {
            $document = $document->newQuery()->onlyTrashed()->lockForUpdate()->findOrFail($document->getKey());
            $dependances = $this->dependencies($document, true);
            if ($dependances !== []) {
                throw ValidationException::withMessages([
                    'dependances' => 'Suppression définitive impossible : restaurez ou supprimez définitivement les dépendances en premier.',
                ]);
            }

            $before = $document->attributesToArray();
            $this->audit->record('suppression_definitive', $document, $this->reference($document), $motif, $before, null, [], $request, 'individuelle');
            if ($document instanceof BRC) {
                $document->journaux()->detach();
                $document->lignes()->delete();
            } elseif ($document instanceof EtatBesoin || $document instanceof EntreeCaisse) {
                $document->lignes()->delete();
            }
            $document->forceDelete();
        });
    }

    public function dependencies(Model $document, bool $withTrashed = false): array
    {
        $items = collect();
        $scope = fn ($query) => $withTrashed ? $query->withTrashed() : $query;

        if ($document instanceof EtatBesoin) {
            $sorties = $scope($document->sortieCaisses())->get();
            $items->push(...$sorties);
            foreach ($sorties as $sortie) {
                $journaux = $scope($sortie->journaux())->get();
                $items->push(...$journaux);
                foreach ($journaux as $journal) $items->push(...$scope($journal->ecritures())->get());
            }
        } elseif ($document instanceof EntreeCaisse) {
            $journaux = $scope($document->journaux())->get();
            $items->push(...$journaux);
            foreach ($journaux as $journal) $items->push(...$scope($journal->ecritures())->get());
        } elseif ($document instanceof SortieCaisse) {
            $journaux = $scope($document->journaux())->get();
            $items->push(...$journaux);
            foreach ($journaux as $journal) $items->push(...$scope($journal->ecritures())->get());
        } elseif ($document instanceof BRC) {
            $journaux = $scope($document->journaux())->get();
            if ($document->journal_id) {
                $legacy = $scope(Journaux::query())->find($document->journal_id);
                if ($legacy) $journaux->push($legacy);
            }
            foreach ($journaux->unique('id') as $journal) {
                $items->push($journal, ...$scope($journal->ecritures())->get());
            }
        } elseif ($document instanceof Journaux) {
            $items->push(...$scope($document->ecritures())->get());
            $items->push(...$scope($document->brcs())->get());
        }

        return $items->filter()->unique(fn (Model $item) => $item::class.'#'.$item->getKey())
            ->map(fn (Model $item) => [
                'type' => $this->label($item),
                'model' => $item::class,
                'id' => $item->getKey(),
                'reference' => $this->reference($item),
                'statut' => $item->statut ?? '—',
            ])->values()->all();
    }

    private function cascadeDocuments(Model $document): Collection
    {
        $documents = collect();
        foreach ($this->dependencies($document) as $dependency) {
            $model = new $dependency['model'];
            $item = $model->newQuery()->find($dependency['id']);
            if ($item) $documents->push($item);
        }

        return $documents
            ->sortBy(fn (Model $item) => $this->deletionOrder($item))
            ->push($document)
            ->unique(fn (Model $item) => $item::class.'#'.$item->getKey())
            ->values();
    }

    private function restoreDocuments(Model $document): Collection
    {
        $documents = $this->parents($document)->push($document);
        foreach ($this->dependencies($document, true) as $dependency) {
            $model = new $dependency['model'];
            $item = $model->newQuery()->onlyTrashed()->find($dependency['id']);
            if ($item) $documents->push($item);
        }

        return $documents->sortByDesc(fn (Model $item) => $this->deletionOrder($item))->values();
    }

    private function parents(Model $document): Collection
    {
        $parents = collect();
        if ($document instanceof EcritureComptable) {
            $journal = Journaux::withTrashed()->find($document->journal_id);
            if ($journal) {
                $parents->push(...$this->parents($journal));
                $parents->push($journal);
            }
        } elseif ($document instanceof Journaux) {
            $bon = $document->entree_caisse_id
                ? EntreeCaisse::withTrashed()->find($document->entree_caisse_id)
                : SortieCaisse::withTrashed()->find($document->sortie_caisse_id);
            if ($bon) {
                $parents->push(...$this->parents($bon));
                $parents->push($bon);
            }
            $brcs = $document->brcs()->withTrashed()->get();
            $parents->push(...$brcs);
        } elseif ($document instanceof SortieCaisse && $document->etat_besoin_id) {
            $etat = EtatBesoin::withTrashed()->find($document->etat_besoin_id);
            if ($etat) $parents->push($etat);
        }
        return $parents->filter()->unique(fn (Model $item) => $item::class.'#'.$item->getKey());
    }

    private function assertParentsActive(Model $document): void
    {
        $deletedParent = $this->parents($document)->first(fn (Model $parent) => $parent->trashed());
        if ($deletedParent) {
            throw ValidationException::withMessages([
                'cascade' => 'Restauration individuelle impossible : le document parent '.$this->reference($deletedParent).' est encore dans la corbeille.',
            ]);
        }
    }

    private function softDeleteOne(Model $document, string $motif, string $strategie, Request $request): void
    {
        if ($document->trashed()) return;
        $before = $document->attributesToArray();
        $dependances = $this->dependencies($document);
        $document->forceFill([
            'motif_suppression' => $motif,
            'supprime_par' => $request->user()->id,
            'restaure_par' => null,
            'restaure_le' => null,
        ])->save();
        $document->delete();

        $action = $strategie === 'cascade'
            ? 'suppression_cascade'
            : (($before['statut'] ?? null) === 'Validé' ? 'suppression_document_valide' : 'suppression');
        $this->audit->record($action, $document, $this->reference($document), $motif, $before, $document->attributesToArray(), $dependances, $request, $strategie === 'cascade' ? 'cascade contrôlée' : 'individuelle');
    }

    private function assertReferenceAvailable(Model $document): void
    {
        $column = $document instanceof Journaux ? 'reference' : (isset($document->numero) ? 'numero' : 'reference');
        $value = $document->{$column};
        if ($value && $document->newQuery()->where($column, $value)->whereKeyNot($document->getKey())->exists()) {
            throw ValidationException::withMessages(['reference' => "Restauration impossible : la référence {$value} est déjà utilisée."]);
        }
    }

    private function reference(Model $document): ?string
    {
        return $document->numero ?? $document->reference ?? $document->piece ?? null;
    }

    private function label(Model $document): string
    {
        return match (true) {
            $document instanceof EtatBesoin => 'État de besoin',
            $document instanceof SortieCaisse => 'Bon de sortie',
            $document instanceof EntreeCaisse => "Bon d'entrée",
            $document instanceof Journaux => 'Journal',
            $document instanceof EcritureComptable => 'Écriture comptable',
            $document instanceof BRC => 'BRC',
            default => class_basename($document),
        };
    }

    private function deletionOrder(Model $document): int
    {
        return match (true) {
            $document instanceof EcritureComptable => 1,
            $document instanceof Journaux => 2,
            $document instanceof SortieCaisse, $document instanceof EntreeCaisse => 3,
            default => 4,
        };
    }
}
