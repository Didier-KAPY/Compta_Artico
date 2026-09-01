<?php
namespace Database\Factories;
use App\Models\{Entreprise,Role,User};
use Illuminate\Database\Eloquent\Factories\Factory;
class EntrepriseFactory extends Factory { protected $model=Entreprise::class; public function definition():array{$role=Role::firstOrCreate(['designation'=>'Super Admin']);$user=User::create(['nom'=>'Admin','prenom'=>'Test','email'=>fake()->unique()->safeEmail(),'password'=>bcrypt('password'),'role_id'=>$role->id,'statut'=>'Actif']);return ['user_id'=>$user->id,'nom_entreprise'=>fake()->company(),'monnaie_budgetaire'=>'CDF'];} }
