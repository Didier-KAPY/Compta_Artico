<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function record(
        string $action,
        Model $model,
        ?string $reference,
        ?string $motif,
        ?array $ancienneValeur,
        ?array $nouvelleValeur,
        array $dependances,
        Request $request,
        ?string $typeSuppression = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $request->user()?->id ?? Auth::id(),
            'action' => $action,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'reference_document' => $reference,
            'ancien_statut' => $ancienneValeur['statut'] ?? null,
            'motif' => $motif,
            'ancienne_valeur' => $ancienneValeur,
            'nouvelle_valeur' => $nouvelleValeur,
            'dependances' => $dependances,
            'type_suppression' => $typeSuppression,
            'adresse_ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
        ]);
    }
}
