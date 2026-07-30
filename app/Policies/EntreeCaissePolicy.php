<?php

namespace App\Policies;

use App\Models\EntreeCaisse;
use App\Models\User;

class EntreeCaissePolicy
{
    public function reouvrir(User $user, EntreeCaisse $entree): bool
    {
        return $user->isSuperAdmin();
    }
}
