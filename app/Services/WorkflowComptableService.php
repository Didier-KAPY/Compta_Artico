<?php

namespace App\Services;

use App\Models\EcritureComptable;
use App\Models\Journaux;
use App\Models\EtatBesoin;
use App\Models\EntreeCaisse;
use App\Models\SortieCaisse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkflowComptableService
{
    public function validerJournal(Journaux $journal, ?int $journalTypeId = null): Journaux
    {
        return DB::transaction(function () use ($journal, $journalTypeId) {
            $locked = Journaux::with(['entreeCaisse', 'sortieCaisse'])
                ->lockForUpdate()->findOrFail($journal->id);
            $this->assertEnAttente($locked->statut, 'Ce journal a déjà été traité.');

            if (! $locked->entree_caisse_id && ! $locked->sortie_caisse_id) {
                $this->fail('Ce journal ne possède aucun Bon lié.');
            }
            if ($locked->entreeCaisse && $locked->entreeCaisse->statut !== 'Validé') {
                $this->fail('Le Bon d’entrée lié doit être validé.');
            }
            if ($locked->sortieCaisse && $locked->sortieCaisse->statut !== 'Validé') {
                $this->fail('Le Bon de sortie lié doit être validé.');
            }

            $locked->update([
                'journal_type_id' => $journalTypeId ?? $locked->journal_type_id,
                'statut' => 'Validé',
                'valide_par' => Auth::id(),
                'date_validation' => now(),
            ]);
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
            if ($locked->ecritures()->where('statut', 'Validé')->exists()) {
                $this->fail('Impossible de réouvrir ce journal : une écriture liée est validée.');
            }
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
            if ($locked->journaux()->where('statut', 'Validé')->exists()) {
                $this->fail('Impossible de réouvrir ce Bon : un Journal lié est validé.');
            }
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
}
