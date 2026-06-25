@extends('layouts.app')

@section('content')

<div class="container py-4">

    <!-- ================= HEADER ================= -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">✏️ Modifier Bon d'Entrée de Caisse</h3>
    </div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <form action="{{ route('entree-caisse.update', $entree->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- ================= INFOS ================= -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white">
                📌 Informations générales
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ $entree->date }}">
                    </div>

                    @php
                        $types = ['caisse', 'banque', 'monnaie électronique'];
                    @endphp

                    <div class="col-md-4">
                        <label class="form-label">Type</label>

                        <select name="type" class="form-select">
                            <option value="">-- Sélectionner --</option>

                            {{-- valeur actuelle en base affichée en premier --}}
                            @if($entree->type)
                                <option value="{{ $entree->type }}" selected>
                                    {{ ucfirst($entree->type) }} (actuel)
                                </option>
                            @endif

                            {{-- autres options --}}
                            @foreach($types as $type)
                                @if($type != $entree->type)
                                    <option value="{{ $type }}">
                                        {{ ucfirst($type) }}
                                    </option>
                                @endif
                            @endforeach

                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Motif</label>
                        <input type="text" name="motif" class="form-control" value="{{ $entree->motif }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Monnaie</label>
                        <select name="monnaie" class="form-select">
                            <option value="">-- Sélectionner --</option>
                            <option value="CDF" {{ $entree->monnaie == 'CDF' ? 'selected' : '' }}>CDF</option>
                            <option value="USD" {{ $entree->monnaie == 'USD' ? 'selected' : '' }}>USD</option>
                        </select>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">Observation</label>
                        <textarea name="observation" class="form-control">{{ $entree->observation }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================= LIGNES ================= -->
        <div class="card shadow-sm border-0 mb-3">

            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                📦 Lignes de l’entrée
                <button type="button" class="btn btn-sm btn-light" id="add-line">
                    ➕ Ajouter
                </button>
            </div>

            <div class="card-body">

                <div id="lignes-wrapper">

                    @foreach($entree->lignes as $index => $ligne)

                        <div class="row g-2 align-items-center ligne mb-2 p-2 border rounded bg-light">

                            <div class="col-md-4">
                                <input type="text" name="lignes[{{ $index }}][designation]"
                                       class="form-control"
                                       value="{{ $ligne->designation }}"
                                       placeholder="Désignation">
                            </div>

                            <div class="col-md-2">
                                <input type="number" name="lignes[{{ $index }}][quantite]"
                                       class="form-control qte"
                                       value="{{ $ligne->quantite }}"
                                       placeholder="Qté">
                            </div>

                            <div class="col-md-3">
                                <input type="number" name="lignes[{{ $index }}][prix_unitaire]"
                                       class="form-control prix"
                                       value="{{ $ligne->prix_unitaire }}"
                                       placeholder="Prix">
                            </div>

                            <div class="col-md-2">
                                <input type="number" class="form-control montant bg-white" readonly
                                       value="{{ $ligne->montant }}">
                            </div>

                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-danger btn-sm remove-line">X</button>
                            </div>

                        </div>

                    @endforeach

                </div>

            </div>
        </div>

        <!-- ================= TOTAL ================= -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">

                <h5 class="mb-0">💰 Total général</h5>

                <h3 class="text-primary mb-0">
                    <span id="total-general">0</span> {{ $entree->monnaie }}
                </h3>

            </div>
        </div>

        <!-- ================= BUTTON ================= -->
        <div class="text-end mb-4">
            <button class="btn btn-success px-4">
                💾 Enregistrer les modifications
            </button>
        </div>

    </form>

</div>

@endsection


@section('styles')
<style>
body{
    background:#f4f6f9;
}

.card{
    border-radius:12px;
}

.form-control, .form-select{
    border-radius:8px;
}

.ligne{
    transition:0.2s;
}

.ligne:hover{
    background:#eef3ff !important;
}
</style>
@endsection


@section('scripts')
<script>

let index = {{ count($entree->lignes) }};

// ================= CALCUL TOTAL =================
function calculerTotal() {

    let total = 0;

    document.querySelectorAll('.ligne').forEach(function(row){

        let q = parseFloat(row.querySelector('.qte')?.value || 0);
        let p = parseFloat(row.querySelector('.prix')?.value || 0);

        let m = q * p;

        let montantInput = row.querySelector('.montant');
        if(montantInput) montantInput.value = m.toFixed(2);

        total += m;
    });

    document.getElementById('total-general').innerText = total.toFixed(2);
}

// ================= INPUT LIVE =================
document.addEventListener('input', function(e){
    if(e.target.classList.contains('qte') || e.target.classList.contains('prix')){
        calculerTotal();
    }
});

// ================= ADD LINE =================
document.getElementById('add-line').addEventListener('click', function () {

    let wrapper = document.getElementById('lignes-wrapper');

    let html = `
        <div class="row g-2 align-items-center ligne mb-2 p-2 border rounded bg-light">

            <div class="col-md-4">
                <input type="text" name="lignes[${index}][designation]" class="form-control" placeholder="Désignation">
            </div>

            <div class="col-md-2">
                <input type="number" name="lignes[${index}][quantite]" class="form-control qte" placeholder="Qté">
            </div>

            <div class="col-md-3">
                <input type="number" name="lignes[${index}][prix_unitaire]" class="form-control prix" placeholder="Prix">
            </div>

            <div class="col-md-2">
                <input type="number" class="form-control montant bg-white" readonly>
            </div>

            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-danger btn-sm remove-line">X</button>
            </div>

        </div>
    `;

    wrapper.insertAdjacentHTML('beforeend', html);
    index++;

    calculerTotal();
});

// ================= REMOVE LINE =================
document.addEventListener('click', function(e){
    if(e.target.classList.contains('remove-line')){
        e.target.closest('.ligne').remove();
        calculerTotal();
    }
});

// ================= INIT =================
calculerTotal();

</script>
@endsection