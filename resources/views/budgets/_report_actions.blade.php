<div class="d-flex gap-2 flex-wrap mb-4 justify-content-end d-print-none">
    <a href="{{ route('parametres.budgets.report', ['rapport' => $rapport, 'format' => 'print']) }}" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Imprimer</a>
    <a href="{{ route('parametres.budgets.report', ['rapport' => $rapport, 'format' => 'pdf']) }}" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
    <a href="{{ route('parametres.budgets.report', ['rapport' => $rapport, 'format' => 'excel']) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
</div>
