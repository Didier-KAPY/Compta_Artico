@extends('layouts.app')

@section('content')

<div class="container mt-0">

    <div class="card border-0">

        <form id="formSave"
              action="{{ route('journal-banque.store') }}"
              method="POST">

            @csrf

            <!-- HEADER -->
            <div class="card-header d-flex justify-content-between align-items-center"
                 style="background-color:#1C2A44;color:white;">

                <select name="journal_type_id" class="form-select form-select-sm" style="width:400px;" required>
                    <option value="">-- Journal Banque --</option>
                    @foreach($comptes as $compte)
                        <option value="{{ $compte->id }}">
                            {{ $compte->designation }}
                        </option>
                    @endforeach
                </select>

                <div style="width:400px;">
                    <label style="font-size:18px;font-weight:bold;color:white;">
                        Taux : <span id="taux">{{ $taux->taux_de_change ?? 1 }}</span> CDF
                    </label>
                </div>

            </div>

            <input type="hidden" name="taux_de_change_id" value="{{ $taux->id ?? '' }}">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Action</label>
                        <select name="action" id="action" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            <option value="entree_cdf">Entrée CDF</option>
                            <option value="entree_usd">Entrée USD</option>
                            <option value="sortie_cdf">Sortie CDF</option>
                            <option value="sortie_usd">Sortie USD</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Client</label>
                        <input type="text" name="clients" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Contact</label>
                        <input type="number" name="contacts" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Compte</label>
                        <select name="liste_des_comptes_id" class="form-select" required>
                            <option value="">-- Compte --</option>
                            @foreach($tousLesComptes as $compte)
                                <option value="{{ $compte->id }}">
                                    {{ $compte->designation }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>

                    <!-- INPUTS -->
                    <div class="col-md-6 mb-3">
                        <label>Entrées CDF</label>
                        <input type="number" step="0.01" name="entrees_cdf" id="entrees_cdf" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Entrées USD</label>
                        <input type="number" step="0.01" name="entrees_usd" id="entrees_usd" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Sorties CDF</label>
                        <input type="number" step="0.01" name="sorties_cdf" id="sorties_cdf" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Sorties USD</label>
                        <input type="number" step="0.01" name="sorties_usd" id="sorties_usd" class="form-control">
                    </div>

                </div>

                <!-- TOTALS -->
                <div class="row mt-4">

                    <div class="col-md-6">
                        <div class="card p-3 bg-success text-white">
                            <h5>Total Entrées (CDF)</h5>
                            <h3 id="total_entree">0</h3>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card p-3 bg-danger text-white">
                            <h5>Total Sorties (CDF)</h5>
                            <h3 id="total_sortie">0</h3>
                        </div>
                    </div>

                </div>

                <button type="submit" class="btn btn-primary mt-3">
                    Enregistrer
                </button>

            </div>
        </form>

    </div>
</div>

@endsection

{{-- ================= JS ================= --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const taux = parseFloat(document.getElementById('taux').innerText || 1);

    const entreeCdf = document.getElementById('entrees_cdf');
    const entreeUsd = document.getElementById('entrees_usd');
    const sortieCdf = document.getElementById('sorties_cdf');
    const sortieUsd = document.getElementById('sorties_usd');

    const totalEntree = document.getElementById('total_entree');
    const totalSortie = document.getElementById('total_sortie');

    function calcEntree() {
        let total = (parseFloat(entreeCdf.value || 0)) + (parseFloat(entreeUsd.value || 0) * taux);
        totalEntree.innerText = total.toFixed(2);
    }

    function calcSortie() {
        let total = (parseFloat(sortieCdf.value || 0)) + (parseFloat(sortieUsd.value || 0) * taux);
        totalSortie.innerText = total.toFixed(2);
    }

    entreeCdf.addEventListener('input', calcEntree);
    entreeUsd.addEventListener('input', calcEntree);

    sortieCdf.addEventListener('input', calcSortie);
    sortieUsd.addEventListener('input', calcSortie);

});
</script>