<?php

namespace App\Policies;

use App\Models\EtatBesoin;
use App\Models\User;

class EtatBesoinPolicy
{
    public function valider(User $user, EtatBesoin $etat): bool
    {
        return $user->hasRole([
            'Super Admin', 'Admin', 'Gérant', 'Gerant', 'Directeur Général',
            'Comptable',
        ]);
    }

    public function reouvrir(User $user, EtatBesoin $etat): bool
    {
        return $user->isSuperAdmin();
    }
}
