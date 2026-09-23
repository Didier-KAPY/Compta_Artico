<?php

namespace App\Services;

use App\Models\EcritureComptable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PiecesJustificativesService
{
    public function liste(Model $document): Collection
    {
        $pieces = collect($document->pieces_justificatives ?? []);
        if ($document->piece_justificative) {
            $pieces->push(['path' => $document->piece_justificative, 'nom' => $document->piece_justificative_nom ?: basename($document->piece_justificative)]);
        }
        if ($document instanceof EcritureComptable) {
            if ($document->role_constatation === 'constatation' && $document->constatation?->source) {
                $pieces = $pieces->concat($this->liste($document->constatation->source));
            }
            $journal = $document->journal;
            if ($journal?->piece_justificatif) {
                $pieces->push(['path' => $journal->piece_justificatif, 'nom' => basename($journal->piece_justificatif)]);
            }
            if ($etat = $journal?->sortieCaisse?->etatBesoin) {
                $pieces = $pieces->concat($this->liste($etat));
            }
            if (filled($document->piece)) {
                foreach (EcritureComptable::whereRaw('UPPER(TRIM(piece)) = ?', [mb_strtoupper(trim($document->piece))])->get() as $ligne) {
                    $pieces = $pieces->concat($ligne->pieces_justificatives ?? []);
                    if ($ligne->piece_justificative) {
                        $pieces->push(['path' => $ligne->piece_justificative, 'nom' => basename($ligne->piece_justificative)]);
                    }
                }
            }
        }
        return $pieces->unique('path')->values();
    }

    public function ajouter(Request $request, Model $document, string $directory, string $mimes, int $max): void
    {
        $request->validate([
            'piece_justificative' => ['required_without:pieces_justificatives', 'file', 'mimes:'.$mimes, 'max:'.$max],
            'pieces_justificatives' => ['required_without:piece_justificative', 'array', 'min:1', 'max:20'],
            'pieces_justificatives.*' => ['required', 'file', 'mimes:'.$mimes, 'max:'.$max],
        ]);
        $files = $request->file('pieces_justificatives', []);
        if ($request->hasFile('piece_justificative')) {
            $files[] = $request->file('piece_justificative');
        }
        $stored = [];
        try {
            foreach ($files as $file) {
                $stored[] = ['path' => $file->store($directory, 'public'), 'nom' => $file->getClientOriginalName()];
            }
            DB::transaction(function () use ($document, $stored) {
                $locked = $document->newQuery()->lockForUpdate()->findOrFail($document->id);
                $locked->pieces_justificatives = array_merge($locked->pieces_justificatives ?? [], $stored);
                if (! $locked->piece_justificative) {
                    $locked->piece_justificative = $stored[0]['path'];
                    if (! $locked instanceof EcritureComptable) {
                        $locked->piece_justificative_nom = $stored[0]['nom'];
                    }
                }
                $locked->save();
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete(array_column($stored, 'path'));
            throw $e;
        }
    }

    public function consulter(Request $request, Model $document)
    {
        $pieces = $this->liste($document);
        $piece = $request->has('piece')
            ? $pieces->first(fn ($piece) => hash('sha256', $piece['path']) === $request->query('piece'))
            : $pieces->first(fn ($piece) => Storage::disk('public')->exists($piece['path']));
        abort_unless($piece && Storage::disk('public')->exists($piece['path']), 404);
        return $request->boolean('telecharger')
            ? Storage::disk('public')->download($piece['path'], $piece['nom'])
            : Storage::disk('public')->response($piece['path'], $piece['nom'], [
                'Content-Disposition' => 'inline; filename="'.str_replace(["\r", "\n", '"', '\\'], '', $piece['nom']).'"',
            ]);
    }

    public function supprimer(Model $document, string $identifiant): array
    {
        $pieceSupprimee = DB::transaction(function () use ($document, $identifiant) {
            $locked = $document->newQuery()->lockForUpdate()->findOrFail($document->getKey());
            $pieces = collect($locked->pieces_justificatives ?? []);
            $legacy = $locked->piece_justificative
                ? ['path' => $locked->piece_justificative, 'nom' => $locked->piece_justificative_nom ?: basename($locked->piece_justificative)]
                : null;
            $piece = $pieces->first(fn (array $item) => hash('sha256', $item['path']) === $identifiant);

            if (! $piece && $legacy && hash('sha256', $legacy['path']) === $identifiant) {
                $piece = $legacy;
            }

            abort_unless($piece, 404);

            $restantes = $pieces
                ->reject(fn (array $item) => $item['path'] === $piece['path'])
                ->values()
                ->all();

            $locked->pieces_justificatives = $restantes;
            if ($locked->piece_justificative === $piece['path']) {
                $suivante = $restantes[0] ?? null;
                $locked->piece_justificative = $suivante['path'] ?? null;
                if (! $locked instanceof EcritureComptable) {
                    $locked->piece_justificative_nom = $suivante['nom'] ?? null;
                }
            }
            $locked->save();

            return $piece;
        });

        Storage::disk('public')->delete($pieceSupprimee['path']);

        return $pieceSupprimee;
    }
}
