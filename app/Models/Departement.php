<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    use HasFactory;

    protected $fillable = ['designation'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function etatBesoins()
    {
        return $this->hasMany(EtatBesoin::class);
    }
}
