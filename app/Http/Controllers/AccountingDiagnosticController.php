<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AccountingDiagnosticController extends Controller
{
    public function __invoke(Request $request)
    {
        $checks = [
            'ecritures' => fn () => app(EcritureComptableController::class)->liste(Request::create('/ecritures', 'GET')),
            'balance' => fn () => app(BalanceController::class)->index(Request::create('/balance', 'GET')),
            'grand_livre' => fn () => app(GrandLivreController::class)->index(Request::create('/grand-livre', 'GET')),
        ];
        $results = [];
        foreach ($checks as $name => $check) {
            try {
                $response = $check();
                if (method_exists($response, 'render')) {
                    $response->render();
                }
                $results[$name] = ['statut' => 'OK'];
            } catch (Throwable $exception) {
                report($exception);
                $results[$name] = [
                    'statut' => 'ERREUR',
                    'type' => $exception::class,
                    'message' => $exception->getMessage(),
                    'fichier' => basename($exception->getFile()),
                    'ligne' => $exception->getLine(),
                ];
            }
        }

        return response()->json([
            'version' => 'accounting-diagnostic-v1',
            'schema' => [
                'constatations_comptables' => Schema::hasTable('constatations_comptables'),
                'ecritures.constatation_id' => Schema::hasColumn('ecritures_comptables', 'constatation_id'),
                'ecritures.role_constatation' => Schema::hasColumn('ecritures_comptables', 'role_constatation'),
                'migration_reparation' => DB::table('migrations')->where('migration', '2026_09_23_120000_repair_constatations_comptables')->exists(),
            ],
            'tests' => $results,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
