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

        $technicalRoles = DB::table('roles')
            ->where('designation', 'like', '%Technique%')
            ->whereRaw('LOWER(designation) <> ?', ['directeur technique'])
            ->orderBy('id')
            ->get();

        if ($technicalRoles->isEmpty()) {
            DB::table('roles')->insert([
                'designation' => 'Chargé technique',
                'observation' => 'Même niveau d\'accès que le Directeur Technique',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $canonicalId = $technicalRoles->first()->id;

        foreach ($technicalRoles->skip(1) as $legacy) {
            DB::table('users')->where('role_id', $legacy->id)->update(['role_id' => $canonicalId]);
            DB::table('roles')->where('id', $legacy->id)->delete();
        }

        DB::table('roles')->where('id', $canonicalId)->update([
            'designation' => 'Chargé technique',
            'observation' => 'Même niveau d\'accès que le Directeur Technique',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->whereRaw('BINARY designation = ?', ['Chargé technique'])->update([
            'designation' => 'Charge Technique',
            'updated_at' => now(),
        ]);
    }
};
