<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $legacyRoles = DB::table('roles')
            ->whereIn('designation', ['Charge Technique', 'Charger Technique'])
            ->get();
        $canonical = DB::table('roles')->where('designation', 'Chargé technique')->first();

        if (! $canonical && $legacyRoles->isNotEmpty()) {
            $canonicalId = $legacyRoles->first()->id;
            DB::table('roles')->where('id', $canonicalId)->update([
                'designation' => 'Chargé technique',
                'observation' => 'Même niveau d\'accès que le Directeur Technique',
                'updated_at' => now(),
            ]);
        } elseif (! $canonical) {
            $canonicalId = DB::table('roles')->insertGetId([
                'designation' => 'Chargé technique',
                'observation' => 'Même niveau d\'accès que le Directeur Technique',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $canonicalId = $canonical->id;
        }

        foreach ($legacyRoles as $legacy) {
            if ($legacy->id === $canonicalId) {
                continue;
            }

            DB::table('users')->where('role_id', $legacy->id)->update(['role_id' => $canonicalId]);
            DB::table('roles')->where('id', $legacy->id)->delete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')->where('designation', 'Chargé technique')->update([
            'designation' => 'Charge Technique',
            'observation' => 'Même niveau d\'accès que le Directeur Technique',
            'updated_at' => now(),
        ]);
    }
};
