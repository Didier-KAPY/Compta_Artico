<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id', 'reference_document',
        'ancien_statut', 'motif', 'ancienne_valeur', 'nouvelle_valeur',
        'dependances', 'type_suppression', 'adresse_ip', 'user_agent',
    ];

    protected $casts = [
        'ancienne_valeur' => 'array',
        'nouvelle_valeur' => 'array',
        'dependances' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
