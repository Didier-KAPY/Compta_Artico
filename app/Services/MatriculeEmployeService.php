<?php

namespace App\Services;

use App\Models\Departement;
use App\Models\Employe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MatriculeEmployeService
{
    public function generer(int $entrepriseId, ?int $directionId = null): string
    {
        return DB::transaction(function () use ($entrepriseId, $directionId) {
            $direction = $directionId ? Departement::find($directionId) : null;
            $prefixe = $this->initiales($direction?->designation);
            $base = $prefixe.'-'.now()->format('Y').'-';
            $dernier = Employe::withTrashed()
                ->where('entreprise_id', $entrepriseId)
                ->where('matricule', 'like', $base.'%')
                ->lockForUpdate()
                ->max('matricule');
            $sequence = $dernier ? ((int) substr($dernier, -5)) + 1 : 1;

            do {
                $matricule = $base.str_pad((string) $sequence++, 5, '0', STR_PAD_LEFT);
            } while (Employe::withTrashed()->where('entreprise_id', $entrepriseId)->where('matricule', $matricule)->exists());

            return $matricule;
        });
    }

    public function initiales(?string $designation): string
    {
        $motsIgnores = ['DE', 'DES', 'DU', 'LA', 'LE', 'LES', 'ET', 'D'];
        $mots = preg_split('/[^A-Z0-9]+/', strtoupper(Str::ascii(trim((string) $designation))), -1, PREG_SPLIT_NO_EMPTY);
        $initiales = collect($mots)->reject(fn (string $mot) => in_array($mot, $motsIgnores, true))->map(fn (string $mot) => $mot[0])->implode('');

        return substr($initiales ?: 'DIR', 0, 8);
    }
}
