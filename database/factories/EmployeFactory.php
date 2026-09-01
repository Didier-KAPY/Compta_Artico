<?php
namespace Database\Factories;
use App\Models\{Employe,Entreprise};
use Illuminate\Database\Eloquent\Factories\Factory;
class EmployeFactory extends Factory { protected $model=Employe::class; public function definition():array{return ['entreprise_id'=>Entreprise::factory(),'matricule'=>fake()->unique()->bothify('EMP-#####'),'nom'=>fake()->lastName(),'prenom'=>fake()->firstName(),'sexe'=>fake()->randomElement(['Homme','Femme']),'date_embauche'=>fake()->date(),'statut'=>'Actif','email'=>fake()->unique()->safeEmail()];} }
