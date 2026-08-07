<?php

namespace App\Policies;

use App\Models\Journaux;
use App\Models\User;

class JournalPolicy
{
    public function update(User $user, Journaux $journal): bool
    {
        return $journal->statut === 'En attente'
            && ($user->hasRole(['Super Admin', 'Comptable']) || $journal->user_id === $user->id);
    }

    public function delete(User $user, Journaux $journal): bool
    {
        return $this->update($user, $journal)
            && ! $journal->entree_caisse_id
            && ! $journal->sortie_caisse_id;
    }

    public function valider(User $user, Journaux $journal): bool
    {
        return $user->hasRole(['Super Admin', 'Comptable', 'Caissier', 'Caissière', 'Trésorier', 'Trésorière']);
    }

    public function reouvrir(User $user, Journaux $journal): bool
    {
        return $user->isSuperAdmin();
    }

    public function rejeter(User $user, Journaux $journal): bool
    {
        return $user->isSuperAdmin();
    }
}
