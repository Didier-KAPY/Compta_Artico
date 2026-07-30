<?php

namespace App\Policies;

use App\Models\EtatBesoin;
use App\Models\User;

class EtatBesoinPolicy
{
    public function valider(User $user, EtatBesoin $etat): bool
    {
        return $user->hasRole([
            'Super Admin', 'Comptable', 'Chef de Service', 'Chef de Département',
        ]);
    }

    public function reouvrir(User $user, EtatBesoin $etat): bool
    {
        return $user->isSuperAdmin();
    }
}
