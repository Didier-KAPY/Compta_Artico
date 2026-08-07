<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class FinancialAuditController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'action' => ['nullable', 'string', 'max:80'],
            'module' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        $query = AuditLog::with('user')
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('module'), fn ($q) => $q->where('model_type', $request->string('module')))
            ->when($request->filled('reference'), fn ($q) => $q->where('reference_document', $request->string('reference')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('date_debut'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_debut')))
            ->when($request->filled('date_fin'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_fin')));

        $audits = $query->latest()->paginate(25)->withQueryString();
        $statistics = [
            'total' => AuditLog::count(),
            'aujourdhui' => AuditLog::whereDate('created_at', today())->count(),
            'documents_valides' => AuditLog::where('action', 'suppression_document_valide')->count(),
            'cascades' => AuditLog::where('action', 'suppression_cascade')->count(),
            'restaurations' => AuditLog::whereIn('action', ['restauration', 'restauration_cascade'])->count(),
            'definitives' => AuditLog::where('action', 'suppression_definitive')->count(),
        ];
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');
        $modules = AuditLog::query()->distinct()->orderBy('model_type')->pluck('model_type');
        $users = User::whereIn('id', AuditLog::whereNotNull('user_id')->select('user_id'))->orderBy('nom')->get();

        return view('parametres.audit.index', compact('audits', 'statistics', 'actions', 'modules', 'users'));
    }
}
