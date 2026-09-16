<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\User;

class RhPayslipSignatureService
{
    public function gerant(Entreprise $entreprise): ?User
    {
        return User::with(['role', 'employe'])->where('statut', 'Actif')->orderBy('id')->get()
            ->first(function (User $user) use ($entreprise): bool {
                if (!in_array(mb_strtolower(trim((string) $user->role?->designation)), ['gérant', 'gerant'], true)) {
                    return false;
                }
                $entrepriseId = $user->employe?->entreprise_id ?? app(CurrentEntreprise::class)->for($user)->id;
                return (int) $entrepriseId === (int) $entreprise->id;
            });
    }
}
