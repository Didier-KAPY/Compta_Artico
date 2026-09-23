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
        | CrÃ©ation ou rÃ©cupÃ©ration du rÃ´le Super Admin
        |--------------------------------------------------------------------------
        */

        $role = Role::firstOrCreate(
            [
                'designation' => 'Super Admin',
            ],
            [
                'observation' => 'Administrateur principal du systÃ¨me',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | CrÃ©ation de l'utilisateur par dÃ©faut
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
                'password_default' => true,
                'statut' => 'Actif',
            ]
        );
    }
}