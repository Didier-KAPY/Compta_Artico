<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    /**
     * Champs modifiables
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'role_id',
         'adresse',
        'password_default',
        'photo',
        'statut',
    ];
    /**
     * Champs cachés
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    /**
     * Cast automatique
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_default' => 'boolean',
    ];
    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Un utilisateur appartient à un rôle
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    /**
     * Un utilisateur possède plusieurs entreprises
     */
    public function entreprises()
    {
        return $this->hasMany(Entreprise::class);
    }
    /**
     * Comptes comptables
     */
    public function comptes()
    {
        return $this->hasMany(ListeDesComptes::class);
    }
    /**
     * Journaux
     */
    public function journaux()
    {
        return $this->hasMany(Journal::class);
    }
    /**
     * Taux de change
     */
    public function tauxDeChanges()
    {
        return $this->hasMany(TauxDeChange::class);
    }
}