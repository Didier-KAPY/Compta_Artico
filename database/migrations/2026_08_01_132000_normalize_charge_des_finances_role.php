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

        $canonical = DB::table('roles')->where('designation', 'Chargé des finances')->first();
        $legacy = DB::table('roles')->where('designation', 'Charge de finance')->first();

        if ($legacy && ! $canonical) {
            DB::table('roles')->where('id', $legacy->id)->update([
                'designation' => 'Chargé des finances',
                'observation' => 'Même niveau d\'accès que le DAF',
                'updated_at' => now(),
            ]);

            return;
        }

        if (! $canonical) {
            $canonicalId = DB::table('roles')->insertGetId([
                'designation' => 'Chargé des finances',
                'observation' => 'Même niveau d\'accès que le DAF',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $canonicalId = $canonical->id;
        }

        if ($legacy) {
            DB::table('users')->where('role_id', $legacy->id)->update(['role_id' => $canonicalId]);
            DB::table('roles')->where('id', $legacy->id)->delete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')->where('designation', 'Chargé des finances')->update([
            'designation' => 'Charge de finance',
            'observation' => 'Même niveau d\'accès que le DAF',
            'updated_at' => now(),
        ]);
    }
};
