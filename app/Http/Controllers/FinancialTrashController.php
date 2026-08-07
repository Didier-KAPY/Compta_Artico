<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForceDeleteFinancialDocumentRequest;
use App\Http\Requests\RestoreFinancialDocumentRequest;
use App\Models\AuditLog;
use App\Models\BRC;
use App\Models\EcritureComptable;
use App\Models\EntreeCaisse;
use App\Models\EtatBesoin;
use App\Models\Journaux;
use App\Models\SortieCaisse;
use App\Services\FinancialDocumentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FinancialTrashController extends Controller
{
    private const MODELS = [
        'etats-besoin' => EtatBesoin::class,
        'bons-sortie' => SortieCaisse::class,
        'bons-entree' => EntreeCaisse::class,
        'journaux' => Journaux::class,
        'ecritures' => EcritureComptable::class,
        'brc' => BRC::class,
    ];

    public function index(Request $request)
    {
        $request->validate([
            'module' => ['nullable', 'in:'.implode(',', array_keys(self::MODELS))],
            'reference' => ['nullable', 'string', 'max:255'],
            'utilisateur' => ['nullable', 'integer'],
            'date_suppression' => ['nullable', 'date'],
            'statut' => ['nullable', 'in:En attente,Validé,Rejeté'],
        ]);

        $modules = $request->filled('module')
            ? [$request->string('module')->toString() => self::MODELS[$request->string('module')->toString()]]
            : self::MODELS;

        $documents = collect();
        foreach ($modules as $key => $class) {
            $model = new $class;
            $referenceColumn = $class === Journaux::class || $class === BRC::class ? 'reference' : ($class === EcritureComptable::class ? 'piece' : 'numero');
            $rows = $class::onlyTrashed()
                ->when($request->filled('reference'), fn ($query) => $query->where($referenceColumn, 'like', '%'.$request->string('reference').'%'))
                ->when($request->filled('utilisateur'), fn ($query) => $query->where('supprime_par', $request->integer('utilisateur')))
                ->when($request->filled('date_suppression'), fn ($query) => $query->whereDate('deleted_at', $request->date('date_suppression')))
                ->when($request->filled('statut'), fn ($query) => $query->where('statut', $request->string('statut')))
                ->get()
                ->map(fn (Model $document) => [
                    'module' => $key,
                    'type' => $this->label($document),
                    'id' => $document->getKey(),
                    'reference' => $document->{$referenceColumn},
                    'date' => $document->date ?? null,
                    'statut' => $document->statut,
                    'motif' => $document->motif_suppression,
                    'supprime_par' => $document->supprime_par,
                    'deleted_at' => $document->deleted_at,
                ]);
            $documents->push(...$rows);
        }

        $documents = $documents->sortByDesc('deleted_at')->values();
        $audits = AuditLog::with('user')->whereIn('action', ['suppression', 'suppression_document_valide', 'suppression_cascade'])
            ->latest()->get()->keyBy(fn (AuditLog $audit) => $audit->model_type.'#'.$audit->model_id);

        $documents = $documents->map(function (array $document) use ($audits) {
            $class = self::MODELS[$document['module']];
            $document['audit'] = $audits->get($class.'#'.$document['id']);
            return $document;
        });

        return view('corbeille.index', ['documents' => $documents, 'modules' => array_keys(self::MODELS)]);
    }

    public function show(string $module, int $id)
    {
        $document = $this->trashed($module, $id);
        $audits = AuditLog::with('user')->where('model_type', $document::class)
            ->where('model_id', $document->getKey())->latest()->get();

        return view('corbeille.show', compact('document', 'module', 'audits'));
    }

    public function restore(RestoreFinancialDocumentRequest $request, string $module, int $id, FinancialDocumentService $service)
    {
        $service->restore($this->trashed($module, $id), $request->boolean('cascade'), $request);
        return redirect()->route('corbeille.index')->with('success', 'Document restauré avec succès.');
    }

    public function forceDelete(ForceDeleteFinancialDocumentRequest $request, string $module, int $id, FinancialDocumentService $service)
    {
        $service->forceDelete($this->trashed($module, $id), $request->validated('motif'), $request);
        return redirect()->route('corbeille.index')->with('success', 'Document supprimé définitivement. Le journal d’audit est conservé.');
    }

    private function trashed(string $module, int $id): Model
    {
        abort_unless(isset(self::MODELS[$module]), 404);
        return self::MODELS[$module]::onlyTrashed()->findOrFail($id);
    }

    private function label(Model $document): string
    {
        return match (true) {
            $document instanceof EtatBesoin => 'État de besoin',
            $document instanceof SortieCaisse => 'Bon de sortie',
            $document instanceof EntreeCaisse => "Bon d'entrée",
            $document instanceof Journaux => 'Journal',
            $document instanceof EcritureComptable => 'Écriture comptable',
            $document instanceof BRC => 'BRC',
        };
    }
}
