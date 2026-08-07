<?php

namespace App\Policies;

use App\Models\SortieCaisse;
use App\Models\User;

class SortieCaissePolicy
{
    public function reouvrir(User $user, SortieCaisse $sortie): bool
    {
        return $user->isSuperAdmin() || $user->isManagement();
    }
}
