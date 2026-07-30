<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Création ou récupération du rôle Super Admin
        |--------------------------------------------------------------------------
        */

        $role = Role::firstOrCreate(
            [
                'nom' => 'Super Admin',
            ],
            [
                'description' => 'Administrateur principal du système',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Création de l'utilisateur par défaut
        |--------------------------------------------------------------------------
        */

        User::updateOrCreate(
            [
                'email' => 'admin@compta-artico.com',
            ],
            [
                'nom' => 'Artico',
                'prenom' => 'Sarlu',
                'role_id' => $role->id,
                'password' => Hash::make('123456'),
                'statut' => 'Actif',
            ]
        );
    }
}