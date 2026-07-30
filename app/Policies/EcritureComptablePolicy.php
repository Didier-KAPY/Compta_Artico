<?php

namespace App\Policies;

use App\Models\EcritureComptable;
use App\Models\User;

class EcritureComptablePolicy
{
    public function valider(User $user, EcritureComptable $ecriture): bool
    {
        return $user->hasRole(['Super Admin', 'Comptable']);
    }

    public function update(User $user, EcritureComptable $ecriture): bool
    {
        return $ecriture->statut !== 'Validé' && $user->hasRole(['Super Admin', 'Comptable']);
    }

    public function delete(User $user, EcritureComptable $ecriture): bool
    {
        return $ecriture->statut !== 'Validé' && $user->hasRole(['Super Admin', 'Comptable']);
    }

    public function reouvrir(User $user, EcritureComptable $ecriture): bool
    {
        return $user->isSuperAdmin();
    }
}
