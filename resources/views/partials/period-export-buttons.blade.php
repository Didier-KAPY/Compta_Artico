@php $params = $exportParams ?? request()->query(); $periodeComplete = !empty($params['date_debut']) && !empty($params['date_fin']); $showPdf = $showPdf ?? true; $showExcel = $showExcel ?? true; @endphp
<div class="d-flex gap-2 flex-wrap">
    @if($periodeComplete)
        @if($showPdf)
        <a href="{{ route('exports.periode', array_merge(['rapport' => $rapport, 'format' => 'pdf'], $params)) }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        @endif
        @if($showExcel)
        <a href="{{ route('exports.periode', array_merge(['rapport' => $rapport, 'format' => 'excel'], $params)) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
        @endif
    @else
        @if($showPdf)
        <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Définissez d'abord la période"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</button>
        @endif
        @if($showExcel)
        <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Définissez d'abord la période"><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
        @endif
    @endif
</div>
