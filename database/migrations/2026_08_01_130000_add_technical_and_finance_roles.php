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

        $now = now();

        DB::table('roles')->insertOrIgnore([
            [
                'designation' => 'Charge Technique',
                'observation' => 'Même niveau d\'accès que le Directeur Technique',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'designation' => 'Charge de finance',
                'observation' => 'Même niveau d\'accès que le DAF',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')
            ->whereIn('designation', ['Charge Technique', 'Charge de finance'])
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.role_id', 'roles.id');
            })
            ->delete();
    }
};
