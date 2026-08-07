<?php

namespace App\Services;

use App\Models\EcritureComptable;
use App\Models\Journaux;
use App\Models\EtatBesoin;
use App\Models\EntreeCaisse;
use App\Models\SortieCaisse;
use App\Models\JournalType;
use App\Models\TauxDeChange;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WorkflowComptableService
{
    public function __construct(private AuditLogService $audit) {}

    public function validerJournal(Journaux $journal, ?int $journalTypeId = null, ?int $compteId = null): Journaux
    {
        return DB::transaction(function () use ($journal, $journalTypeId, $compteId) {
            $locked = Journaux::with(['entreeCaisse', 'sortieCaisse'])
                ->lockForUpdate()->findOrFail($journal->id);
            $this->assertEnAttente($locked->statut, 'Ce journal a déjà été traité.');

            $brcCloture = $locked->brcs()->where('origine', 'cloture')->first();
            if (! $locked->entree_caisse_id && ! $locked->sortie_caisse_id && ! $brcCloture) {
                $this->fail('Ce journal ne possède aucun Bon lié.');
            }
            if ($locked->entreeCaisse && $locked->entreeCaisse->statut !== 'Validé') {
                $this->fail('Le Bon d’entrée lié doit être validé.');
            }
            if ($locked->sortieCaisse && $locked->sortieCaisse->statut !== 'Validé') {
                $this->fail('Le Bon de sortie lié doit être validé.');
            }
            if ($brcCloture && $brcCloture->statut !== 'Validé') {
                $this->fail('Le BRC quotidien lié doit être validé.');
            }

            $locked->update([
                'journal_type_id' => $journalTypeId ?? $locked->journal_type_id,
                'liste_des_comptes_id' => $compteId ?? $locked->liste_des_comptes_id,
                'statut' => 'Validé',
                'valide_par' => Auth::id(),
                'date_validation' => now(),
            ]);

            if (! $locked->ecritures()->exists()) {
                $type = JournalType::find($journalTypeId ?? $locked->journal_type_id);
                $compteTresorerieId = $type?->liste_des_comptes_id;
                $compteOperationId = $compteId ?? $locked->liste_des_comptes_id;

                if (! $compteTresorerieId || ! $compteOperationId) {
                    $this->fail('Le type de journal et le libellé doivent être associés à des comptes.');
                }
                if ((int) $compteTresorerieId === (int) $compteOperationId) {
                    $this->fail('Le libellé doit être différent du compte de trésorerie.');
                }

                $reference = mb_strtoupper(trim((string) $locked->reference));
                if ($brcCloture) {
                    $estEntree = $brcCloture->sens === 'debit';
                } elseif ($locked->entree_caisse_id) {
                    $estEntree = true;
                } elseif ($locked->sortie_caisse_id) {
                    $estEntree = false;
                } else {
                    $this->fail('Impossible de déterminer le sens comptable du journal.');
                }

                $montantUsd = $brcCloture
                    ? ($locked->monnaie === 'USD' ? (float) $locked->montant_ttc : 0)
                    : ($estEntree ? (float) $locked->entrees_usd : (float) $locked->sorties_usd);
                $taux = 0.0;
                if ($montantUsd > 0) {
                    $taux = (float) (TauxDeChange::latest()->value('taux_de_change') ?? 0);
                    if ($taux <= 0) {
                        $this->fail('Un taux de change valide est requis pour comptabiliser ce journal en CDF.');
                    }
                }

                $montantCdf = $brcCloture
                    ? ($locked->monnaie === 'CDF' ? (float) $locked->montant_ttc : 0)
                    : ($estEntree ? (float) $locked->entrees_cdf : (float) $locked->sorties_cdf);
                $montant = $montantCdf + ($montantUsd * $taux);

                if ($montant <= 0) {
                    $this->fail('Le montant du journal doit être supérieur à zéro.');
                }

                $commun = [
                    'user_id' => Auth::id() ?? $locked->user_id,
                    'journal_id' => $locked->id,
                    'date' => $locked->date,
                    'piece' => $locked->reference,
                    'libelle' => $locked->description ?: $locked->reference,
                    'statut' => 'En attente',
                    'valide_par' => null,
                    'date_validation' => null,
                ];

                EcritureComptable::create($commun + [
                    'liste_des_comptes_id' => $estEntree ? $compteTresorerieId : $compteOperationId,
                    'debit_cdf' => $montant,
                    'credit_cdf' => 0,
                ]);
                EcritureComptable::create($commun + [
                    'liste_des_comptes_id' => $estEntree ? $compteOperationId : $compteTresorerieId,
                    'debit_cdf' => 0,
                    'credit_cdf' => $montant,
                ]);
            }
            return $locked->refresh();
        });
    }

    public function validerEcriture(EcritureComptable $ecriture): EcritureComptable
    {
        return DB::transaction(function () use ($ecriture) {
            $locked = EcritureComptable::lockForUpdate()->findOrFail($ecriture->id);
            $this->assertEnAttente($locked->statut, 'Cette écriture a déjà été traitée.');
            $locked->update([
                'statut' => 'Validé',
                'valide_par' => Auth::id(),
                'date_validation' => now(),
            ]);
            return $locked->refresh();
        });
    }

    public function reouvrirJournal(Journaux $journal): Journaux
    {
        return DB::transaction(function () use ($journal) {
            $locked = Journaux::lockForUpdate()->findOrFail($journal->id);
            $locked->ecritures()->get()->each(fn (EcritureComptable $ecriture) => $this->softDeleteForWorkflow(
                $ecriture,
                'Suppression automatique lors de la réouverture du journal.'
            ));
            $locked->update(['statut' => 'En attente', 'valide_par' => null, 'date_validation' => null]);
            return $locked->refresh();
        });
    }

    public function reouvrirEcriture(EcritureComptable $ecriture): EcritureComptable
    {
        return DB::transaction(function () use ($ecriture) {
            $locked = EcritureComptable::lockForUpdate()->findOrFail($ecriture->id);
            $locked->update(['statut' => 'En attente', 'valide_par' => null, 'date_validation' => null]);
            return $locked->refresh();
        });
    }

    public function reouvrirEntreeCaisse(EntreeCaisse $entree): EntreeCaisse
    {
        return DB::transaction(function () use ($entree) {
            $locked = EntreeCaisse::lockForUpdate()->findOrFail($entree->id);
            $journaux = Journaux::query()
                ->where(function ($query) use ($locked) {
                    $query->where('entree_caisse_id', $locked->id)
                        ->orWhere('reference', $locked->numero);
                })
                ->lockForUpdate()
                ->get();

            if ($journaux->contains(fn (Journaux $journal) => mb_strtolower(trim((string) $journal->statut)) === 'validé')) {
                $this->fail('Impossible de remettre ce Bon d’entrée en attente : le Journal lié est déjà validé.');
            }

            // Les journaux encore provisoires ne doivent plus subsister lorsque
            // le bon d'entrée retourne en attente.
            $journaux->each(fn (Journaux $journal) => $this->softDeleteForWorkflow(
                $journal,
                "Suppression automatique lors de la réouverture du Bon d’entrée."
            ));

            $locked->update(['statut' => 'En attente', 'valide_par' => null, 'date_validation' => null]);
            return $locked->refresh();
        });
    }

    public function reouvrirSortieCaisse(SortieCaisse $sortie): SortieCaisse
    {
        return DB::transaction(function () use ($sortie) {
            $locked = SortieCaisse::lockForUpdate()->findOrFail($sortie->id);
            if ($locked->journaux()->where('statut', 'Validé')->exists()) {
                $this->fail('Impossible de réouvrir ce Bon : un Journal lié est validé.');
            }
            $locked->update(['statut' => 'En attente', 'valide_par' => null, 'date_validation' => null]);
            return $locked->refresh();
        });
    }

    public function reouvrirEtatBesoin(EtatBesoin $etat): EtatBesoin
    {
        return DB::transaction(function () use ($etat) {
            $locked = EtatBesoin::lockForUpdate()->findOrFail($etat->id);
            if ($locked->sortieCaisses()->where('statut', 'Validé')->exists()) {
                $this->fail('Impossible de réouvrir cet État : un Bon de sortie lié est validé.');
            }
            $locked->update(['statut' => 'En attente', 'valide_par' => null, 'date_validation' => null]);
            return $locked->refresh();
        });
    }

    private function assertEnAttente(string $statut, string $message): void
    {
        if ($statut !== 'En attente') {
            $this->fail($message);
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['statut' => $message]);
    }

    private function softDeleteForWorkflow($document, string $motif): void
    {
        $before = $document->attributesToArray();
        $document->forceFill([
            'motif_suppression' => $motif,
            'supprime_par' => Auth::id(),
            'restaure_par' => null,
            'restaure_le' => null,
        ])->save();
        $document->delete();
        $this->audit->record(
            'suppression', $document, $document->numero ?? $document->reference ?? $document->piece ?? null,
            $motif, $before, $document->attributesToArray(), [], request(), 'cascade contrôlée'
        );
    }
}
