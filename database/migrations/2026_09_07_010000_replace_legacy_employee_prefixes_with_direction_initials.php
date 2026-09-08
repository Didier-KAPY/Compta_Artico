<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        $initiales = static function (?string $designation): string {
            $motsIgnores = ['DE', 'DES', 'DU', 'LA', 'LE', 'LES', 'ET', 'D'];
            $mots = preg_split('/[^A-Z0-9]+/', strtoupper(Str::ascii(trim((string) $designation))), -1, PREG_SPLIT_NO_EMPTY);
            $code = collect($mots)
                ->reject(fn (string $mot) => in_array($mot, $motsIgnores, true))
                ->map(fn (string $mot) => $mot[0])
                ->implode('');

            return substr($code ?: 'DIR', 0, 8);
        };

        $annee = now()->format('Y');
        $employes = DB::table('rh_employes')
            ->leftJoin('departements', 'departements.id', '=', 'rh_employes.departement_id')
            ->where('rh_employes.matricule', 'like', 'EMP-%')
            ->orderBy('rh_employes.entreprise_id')
            ->orderBy('rh_employes.id')
            ->get([
                'rh_employes.id',
                'rh_employes.entreprise_id',
                'departements.designation as direction',
            ]);

        foreach ($employes as $employe) {
            $base = $initiales($employe->direction).'-'.$annee.'-';
            $dernier = DB::table('rh_employes')
                ->where('entreprise_id', $employe->entreprise_id)
                ->where('matricule', 'like', $base.'%')
                ->max('matricule');
            $sequence = $dernier ? ((int) substr($dernier, -5)) + 1 : 1;

            do {
                $matricule = $base.str_pad((string) $sequence++, 5, '0', STR_PAD_LEFT);
            } while (DB::table('rh_employes')->where('entreprise_id', $employe->entreprise_id)->where('matricule', $matricule)->exists());

            DB::table('rh_employes')->where('id', $employe->id)->update([
                'matricule' => $matricule,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Les matricules sont des identifiants métier : une conversion inverse
        // automatique risquerait de recréer des doublons ou de casser leur historique.
    }
};
