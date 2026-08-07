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
        'departement_id',
        'fonction_id',
         'adresse',
        'password_default',
        'photo',
        'signature',
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

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function fonction()
    {
        return $this->belongsTo(Fonction::class);
    }

    public function cartesService()
    {
        return $this->hasMany(CarteService::class);
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
        return $this->hasMany(Journaux::class);
    }
    /**
     * Taux de change
     */
    public function tauxDeChanges()
    {
        return $this->hasMany(TauxDeChange::class);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $current = mb_strtolower(trim((string) $this->role?->designation));
        $allowed = array_map(
            static fn (string $role): string => mb_strtolower(trim($role)),
            $roles
        );

        $managementRoles = ['admin', 'directeur général', 'gérant', 'gerant'];
        if (in_array($current, $managementRoles, true)
            && array_intersect($allowed, $managementRoles) !== []) {
            return true;
        }

        $accountingRoles = [
            'daf', 'comptable', 'chargé des finances',
            'chargé de finance', 'charge de finance', 'charger de finance',
        ];
        if (in_array($current, $accountingRoles, true)
            && array_intersect($allowed, $accountingRoles) !== []) {
            return true;
        }

        $technicalRoles = ['directeur technique', 'chargé technique', 'charge technique', 'charger technique'];
        if (in_array($current, $technicalRoles, true)
            && array_intersect($allowed, $technicalRoles) !== []) {
            return true;
        }

        return in_array($current, $allowed, true);
    }

    public function isManagement(): bool
    {
        return $this->hasRole(['Admin', 'Directeur Général', 'Gérant', 'Gerant']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(['Super Admin', 'Super admin', 'super_admin']);
    }

    public function isAccounting(): bool
    {
        return $this->hasRole(['DAF', 'Comptable']);
    }
}
