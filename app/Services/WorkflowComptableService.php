<?php

namespace App\Services;

use App\Models\EcritureComptable;
use App\Models\Journaux;
use App\Models\EtatBesoin;
use App\Models\EntreeCaisse;
use App\Models\SortieCaisse;
use App\Models\JournalType;
use App\Models\ParametrageComptable;
use App\Models\TauxDeChange;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WorkflowComptableService
{
    public function __construct(private AuditLogService $audit) {}

    public function validerJournalAvecTva(Journaux $journal, ?int $journalTypeId = null, ?int $compteId = null): Journaux
    {
        return DB::transaction(function () use ($journal, $journalTypeId, $compteId) {
            $selection = Journaux::lockForUpdate()->findOrFail($journal->id);
            $principal = $selection;

            if (mb_strtolower(trim((string) $selection->description)) === 'tva') {
                $principal = Journaux::query()
                    ->when(
                        $selection->entree_caisse_id,
                        fn ($query) => $query->where('entree_caisse_id', $selection->entree_caisse_id)->where('type', 'recette')
                    )
                    ->when(
                        $selection->sortie_caisse_id,
                        fn ($query) => $query->where('sortie_caisse_id', $selection->sortie_caisse_id)->where('type', 'depense')
                    )
                    ->lockForUpdate()
                    ->first() ?? $selection;
            }

            if ($principal->is($selection) && mb_strtolower(trim((string) $principal->description)) === 'tva') {
                $this->fail('La ligne principale du Bon est introuvable.');
            }

            if ($principal->statut === 'En attente') {
                $principal = $this->validerJournal($principal, $journalTypeId, $compteId);
            } elseif ($principal->statut !== 'Validé') {
                $this->fail('Le journal principal a déjà été traité.');
            }

            if ($principal->entree_caisse_id || $principal->sortie_caisse_id) {
                $journauxTva = Journaux::query()
                    ->when($principal->entree_caisse_id, fn ($query) => $query->where('entree_caisse_id', $principal->entree_caisse_id))
                    ->when($principal->sortie_caisse_id, fn ($query) => $query->where('sortie_caisse_id', $principal->sortie_caisse_id))
                    ->whereRaw('LOWER(TRIM(description)) = ?', ['tva'])
                    ->where('statut', 'En attente')
                    ->lockForUpdate()
                    ->get();

                foreach ($journauxTva as $journalTva) {
                    $typeId = $journalTypeId ?? $principal->journal_type_id;
                    $compteTvaId = (int) $journalTva->liste_des_comptes_id;
                    $compteTresorerieId = (int) JournalType::whereKey($typeId)->value('liste_des_comptes_id');

                    // Compatibilité avec les journaux générés avant que le compte
                    // TVA soit enregistré séparément du compte de trésorerie.
                    if ($compteTvaId === $compteTresorerieId) {
                        $codes = $principal->entree_caisse_id
                            ? ['TVA_FACTUREE', 'TVA_DUE']
                            : ['TVA_RECUPERABLE'];
                        $compteTvaId = (int) ParametrageComptable::query()
                            ->whereIn('code', $codes)
                            ->orderByRaw(
                                'CASE code '.collect($codes)->map(
                                    fn ($code, $index) => "WHEN '{$code}' THEN {$index}"
                                )->implode(' ').' ELSE 99 END'
                            )
                            ->value('liste_des_comptes_id');
                    }

                    if (! $compteTvaId || $compteTvaId === $compteTresorerieId) {
                        $this->fail('Aucun compte TVA adapté n’est configuré dans les paramétrages comptables.');
                    }

                    $this->validerJournal($journalTva, $typeId, $compteTvaId);

                    $ecritureTresorerie = $principal->ecritures()
                        ->lockForUpdate()
                        ->first();

                    if (! $ecritureTresorerie) {
                        $this->fail('L’écriture de trésorerie du journal principal est introuvable.');
                    }

                    $montantTvaCdf = max(
                        (float) $journalTva->entrees_cdf,
                        (float) $journalTva->sorties_cdf
                    );
                    $montantTvaUsd = max(
                        (float) $journalTva->entrees_usd,
                        (float) $journalTva->sorties_usd
                    );

                    if ($montantTvaUsd > 0) {
                        $taux = (float) (TauxDeChange::latest()->value('taux_de_change') ?? 0);
                        if ($taux <= 0) {
                            $this->fail('Un taux de change valide est requis pour intégrer la TVA en CDF.');
                        }
                        $montantTvaCdf += $montantTvaUsd * $taux;
                    }

                    $ecritureTresorerie->update([
                        'debit_cdf' => (float) $ecritureTresorerie->debit_cdf > 0
                            ? (float) $ecritureTresorerie->debit_cdf + $montantTvaCdf
                            : 0,
                        'credit_cdf' => (float) $ecritureTresorerie->credit_cdf > 0
                            ? (float) $ecritureTresorerie->credit_cdf + $montantTvaCdf
                            : 0,
                    ]);
                }
            }

            return $principal->refresh();
        });
    }

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
                $estTva = mb_strtolower(trim((string) $locked->description)) === 'tva';
                $contrepartieDifferee = ! $brcCloture
                    && ! $estTva
                    && ($locked->entree_caisse_id || $locked->sortie_caisse_id);

                if (! $compteTresorerieId || (! $contrepartieDifferee && ! $compteOperationId)) {
                    $this->fail('Le type de journal et le libellé doivent être associés à des comptes.');
                }
                if (! $contrepartieDifferee && (int) $compteTresorerieId === (int) $compteOperationId) {
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

                if ($contrepartieDifferee) {
                    EcritureComptable::create($commun + [
                        'liste_des_comptes_id' => $compteTresorerieId,
                        'debit_cdf' => $estEntree ? $montant : 0,
                        'credit_cdf' => $estEntree ? 0 : $montant,
                    ]);
                } elseif ($estTva && ($locked->entree_caisse_id || $locked->sortie_caisse_id)) {
                    // La TVA est fusionnée dans la ligne de trésorerie du journal
                    // principal. Aucune écriture TVA séparée n’est créée.
                } else {
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
