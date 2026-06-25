@extends('layouts.app')

@section('content')

<div class="container mt-0">

    <div class="card border-0">

        <!-- ================= FORMULAIRE ================= -->
        <form id="formSave"
              action="{{ route('tresorerie.journal-caisse.store') }}"
              method="POST">

            @csrf

            <!-- ================= HEADER ================= -->
            <div class="card-header border-0 d-flex justify-content-between align-items-center"
                 style="background-color:#1C2A44;color:white;">

                <!-- Journal -->
                <select name="journal_type_id"
                        class="form-select form-select-sm"
                        style="width:400px;"
                        required>

                    <option value="">-- Journal Caisse --</option>

                    @foreach($comptes as $compte)
                        <option value="{{ $compte->id }}">
                            {{ Str::limit($compte->designation,50) }}
                        </option>
                    @endforeach

                </select>

                <!-- Taux -->
                <div style="width:400px;">
                    <label class="mb-1" style="font-size:22px;font-weight:bold;">
                        <span style="color:white;">Taux de change :</span>
                        <span style="color:#f8f6f6;">
                            {{ $taux->taux_de_change ?? 'Non défini' }}
                        </span>
                        CDF
                    </label>
                </div>

            </div>
            <!-- ================= FIN HEADER ================= -->


            <!-- ================= HIDDEN ================= -->
            <input type="hidden" name="taux_de_change_id" value="{{ $taux->id ?? '' }}">


            <!-- ================= BODY ================= -->
            <div class="card-body">

                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif


                <div class="row">

                    <!-- ACTION -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Action*</label>
                        <select id="action" name="action" class="form-select" required>
                            <option value="">-- Choisir une action --</option>
                            <option value="entree_cdf">Entrée CDF</option>
                            <option value="entree_usd">Entrée USD</option>
                            <option value="sortie_cdf">Sortie CDF</option>
                            <option value="sortie_usd">Sortie USD</option>
                        </select>
                    </div>

                    <!-- CLIENT -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Client *</label>
                        <input type="text" name="clients" class="form-control" required>
                    </div>

                    <!-- NUMBER -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact *</label>
                        <input type="number" name="contacts" class="form-control" required>
                    </div>

                    <!-- DATE -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <!-- COMPTE -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Compte</label>
                        <select name="liste_des_comptes_id" class="form-select" required>
                            <option value="">-- Sélectionner un compte --</option>
                            @foreach($tousLesComptes as $compte)
                                <option value="{{ $compte->id }}">
                                    {{ $compte->designation }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                   

                    <!-- LIBELLE -->
                    <div class="col-md-12 mb-4">
                        <label class="form-label">Libellé</label>
                        <textarea name="description" rows="4" class="form-control"></textarea>
                    </div>

                    <!-- ENTREES CDF -->
                    <div class="col-md-12 mb-3 field" id="entree_cdf">
                        <label class="form-label">Entrées CDF</label>
                        <input type="number" step="0.01" name="entrees_cdf" id="entrees_cdf" class="form-control">
                    </div>

                    <!-- SORTIES CDF -->
                    <div class="col-md-12 mb-3 field" id="sortie_cdf">
                        <label class="form-label">Sorties CDF</label>
                        <input type="number" step="0.01" name="sorties_cdf" id="sorties_cdf" class="form-control">
                    </div>

                    <!-- ENTREES USD -->
                    <div class="col-md-12 mb-3 field" id="entree_usd">
                        <label class="form-label">Entrées USD</label>
                        <input type="number" step="0.01" name="entrees_usd" id="entrees_usd" class="form-control">
                    </div>

                    <!-- SORTIES USD -->
                    <div class="col-md-12 mb-3 field" id="sortie_usd">
                        <label class="form-label">Sorties USD</label>
                        <input type="number" step="0.01" name="sorties_usd" id="sorties_usd" class="form-control">
                    </div>

                </div>


                <!-- ================= TOTALS ================= -->
                <div class="row mb-3">

                    <!-- TOTAL ENTREES -->
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0" style="background:#e8f5e9;">
                            <div class="card-body d-flex justify-content-between">
                                <div>
                                    <h6 class="text-success">CDF</h6>
                                    <h4 class="text-success">
                                        <span id="total_cdf">
                                            {{
                                                ($total_entrees_cdf ?? 0)
                                                +
                                                ($total_entrees_usd ?? 0) * ($taux->taux_de_change ?? 1)
                                            }}
                                        </span>
                                    </h4>
                                </div>
                                <div style="font-size:40px;color:green;">📈</div>
                            </div>
                        </div>
                    </div>

                    <!-- TOTAL SORTIES -->
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0" style="background:#ffebee;">
                            <div class="card-body d-flex justify-content-between">
                                <div>
                                    <h6 class="text-danger">CDF</h6>
                                    <h4 class="text-danger">
                                        <span id="total_sortie_cdf">
                                            {{
                                                ($total_sorties_cdf ?? 0)
                                                +
                                                ($total_sorties_usd ?? 0) * ($taux->taux_de_change ?? 1)
                                            }}
                                        </span>
                                    </h4>
                                </div>
                                <div style="font-size:40px;color:red;">📉</div>
                            </div>
                        </div>
                    </div>

                </div>


                <!-- ================= BUTTON ================= -->
                <button type="button" class="btn btn-primary px-4" id="btnSave">
                    Enregistrer
                </button>

            </div>
        </form>
    </div>
</div>

@endsection


<!-- ================= SWEET ALERT ================= -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .field { display: none; }
</style>

<script>

    document.addEventListener('DOMContentLoaded', function () {

    // ================= ACTION =================
    const action = document.getElementById('action');
    const fields = document.querySelectorAll('.field');

    function hideAll() {
        fields.forEach(f => f.style.display = 'none');
    }

    action.addEventListener('change', function () {

        hideAll();

        if (this.value === 'entree_cdf') document.getElementById('entree_cdf').style.display = 'block';
        if (this.value === 'entree_usd') document.getElementById('entree_usd').style.display = 'block';
        if (this.value === 'sortie_cdf') document.getElementById('sortie_cdf').style.display = 'block';
        if (this.value === 'sortie_usd') document.getElementById('sortie_usd').style.display = 'block';

    });

    hideAll();


    // ================= TAUX =================
    const taux = {{ $taux->taux_de_change ?? 0 }};


    // ================= INPUTS =================
    const entreeUsd = document.getElementById('entrees_usd');
    const sortieUsd = document.getElementById('sorties_usd');
    const entreeCdf = document.getElementById('entrees_cdf');
    const sortieCdf = document.getElementById('sorties_cdf');


    // ================= TOTALS =================
    const totalCdf = document.getElementById('total_cdf');
    const totalSortieCdf = document.getElementById('total_sortie_cdf');


    // ================= CALCUL ENTREES =================
    function updateEntree() {

        let total =
            (parseFloat(entreeCdf?.value || 0)) +
            (parseFloat(entreeUsd?.value || 0) * taux);

        totalCdf.innerText = total.toFixed(2);
    }


    // ================= CALCUL SORTIES =================
    function updateSortie() {

        let total =
            (parseFloat(sortieCdf?.value || 0)) +
            (parseFloat(sortieUsd?.value || 0) * taux);

        totalSortieCdf.innerText = total.toFixed(2);
    }


    entreeUsd?.addEventListener('input', updateEntree);
    entreeCdf?.addEventListener('input', updateEntree);
    sortieUsd?.addEventListener('input', updateSortie);
    sortieCdf?.addEventListener('input', updateSortie);


    // ================= SUBMIT =================
    document.getElementById('btnSave').addEventListener('click', function () {

        if (!action.value) {
            Swal.fire('Attention', 'Choisissez une action', 'warning');
            return;
        }

        Swal.fire({
            title: 'Confirmation',
            text: 'Voulez-vous enregistrer ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Oui',
            cancelButtonText: 'Non'
        }).then((result) => {

            if (result.isConfirmed) {
                document.getElementById('formSave').submit();
            }

        });

    });

});

</script>