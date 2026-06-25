@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
    {{-- TITRE --}}
    <div class="mb-4">
        <h3 class="fw-bold">
            📊 Situation de la trésorerie
        </h3>
        <small class="text-muted">
            Suivi des mouvements Caisse - Banque - Mobile Money
        </small>
    </div>
    {{-- FILTRE --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            🔎 Filtre période
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">
                        Date début
                    </label>
                    <input
                    type="date"
                    name="date_debut"
                    value="{{ $dateDebut }}"
                    class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">
                        Date fin
                    </label>
                    <input
                    type="date"
                    name="date_fin"
                    value="{{ $dateFin }}"
                    class="form-control">
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                        Afficher
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLEAU --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            📋 Détail trésorerie par compte
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Compte</th>
                        <th>Désignation</th>
                        <th class="text-end">Entrée CDF</th>
                        <th class="text-end">Sortie CDF</th>
                        <th class="text-end">Solde CDF</th>
                        <th class="text-end">Entrée USD</th>
                        <th class="text-end">Sortie USD</th>
                        <th class="text-end">Solde USD</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($tresorerie as $ligne)
                    <tr>
                        {{-- COMPTE --}}
                        <td>
                            <strong>
                                {{ $ligne->journalType->compte->compte ?? '' }}
                            </strong>
                        </td>
                        {{-- DESIGNATION --}}
                        <td>
                            {{ $ligne->journalType->compte->designation 
                            ?? $ligne->journalType->compte->libelle 
                            ?? '' }}
                        </td>

                        {{-- CDF --}}
                        <td class="text-end text-success">
                            {{ number_format($ligne->entree_cdf ?? 0,2,',',' ') }}
                        </td>
                        <td class="text-end text-danger">
                            {{ number_format($ligne->sortie_cdf ?? 0,2,',',' ') }}
                        </td>
                        <td class="text-end fw-bold">
                            {{ number_format(
                            ($ligne->entree_cdf ?? 0)
                            -
                            ($ligne->sortie_cdf ?? 0),
                            2,
                            ',',
                            ' '
                            ) }}
                        </td>
                        {{-- USD --}}
                        <td class="text-end text-success">
                            {{ number_format($ligne->entree_usd ?? 0,2,',',' ') }}
                        </td>
                        <td class="text-end text-danger">
                            {{ number_format($ligne->sortie_usd ?? 0,2,',',' ') }}
                        </td>

                        <td class="text-end fw-bold">
                            {{ number_format(
                            ($ligne->entree_usd ?? 0)
                            -
                            ($ligne->sortie_usd ?? 0),
                            2,
                            ',',
                            ' '
                            ) }}
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            Aucun mouvement trouvé
                        </td>
                    </tr>
                @endforelse

                </tbody>
            </table>
        </div>
    </div>
{{-- ================================
        ETAT DE CAISSE
================================ --}}

<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            {{-- HEADER --}}
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-wallet2 me-2"></i>
                    État de caisse par compte
                </h5>
                <span class="badge bg-light text-dark">
                    Validé uniquement
                </span>
            </div>
            {{-- BODY --}}
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    Compte
                                </th>
                                <th>
                                    Désignation
                                </th>
                                <th class="text-center">
                                    Solde CDF
                                </th>
                                <th class="text-center">
                                    Solde USD
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($totaux['etat_caisse'] as $etat)
                            <tr>
                                {{-- COMPTE --}}
                                <td class="fw-bold">
                                    <i class="bi bi-bank me-2"></i>
                                    {{ $etat['compte'] }}
                                </td>

                                {{-- DESIGNATION --}}
                                <td>
                                    {{ $etat['designation'] }}
                                </td>

                                {{-- SOLDE CDF --}}
                                <td class="text-right fw-bold text-success">
                                    {{ number_format(
                                        $etat['solde_cdf'],
                                        2,
                                        ',',
                                        ' '
                                    ) }}
                                    <span class="badge bg-success">
                                        CDF
                                    </span>
                                </td>

                                {{-- SOLDE USD --}}
                                <td class="text-right fw-bold text-primary">
                                    {{ number_format(
                                        $etat['solde_usd'],
                                        2,
                                        ',',
                                        ' '
                                    ) }}
                                    <span class="badge bg-primary">
                                        USD
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4"
                                    class="text-center text-muted">
                                    Aucun mouvement de trésorerie validé
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
    {{-- RESUME --}}
<div class="row g-4">

    {{-- SITUATION CDF --}}

    <div class="col-md-6">

        <div class="card shadow border-primary">


            <div class="card-header bg-primary text-white">

                🇨🇩 Situation CDF

            </div>


            <div class="card-body">


                <p>
                    Entrées :
                    <b class="text-success">

                        {{ number_format($totaux['cdf_entree'],2,',',' ') }}

                    </b>
                </p>



                <p>
                    Sorties :
                    <b class="text-danger">

                        {{ number_format($totaux['cdf_sortie'],2,',',' ') }}

                    </b>
                </p>



                <hr>


                <h4>

                    Solde :

                    {{ number_format($totaux['cdf_solde'],2,',',' ') }}

                    CDF

                </h4>


            </div>


        </div>


    </div>





    {{-- SITUATION USD --}}


    <div class="col-md-6">


        <div class="card shadow border-success">


            <div class="card-header bg-success text-white">

                🇺🇸 Situation USD

            </div>



            <div class="card-body">


                <p>
                    Entrées :

                    <b class="text-success">

                        {{ number_format($totaux['usd_entree'],2,',',' ') }}

                    </b>

                </p>




                <p>

                    Sorties :

                    <b class="text-danger">

                        {{ number_format($totaux['usd_sortie'],2,',',' ') }}

                    </b>

                </p>



                <hr>



                <h4>

                    Solde :

                    {{ number_format($totaux['usd_solde'],2,',',' ') }}

                    USD

                </h4>



            </div>


        </div>


    </div>



</div>

{{-- MOYENS DE TRESORERIE EN BAS --}}
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card shadow border-warning">
            <div class="card-header bg-warning">

                💰 Moyens de trésorerie

            </div>
            <div class="card-body">
                <div class="row">
                    {{-- CAISSE --}}
                    <div class="col-md-4 text-center">
                        <span class="badge bg-primary">
                            Caisse
                        </span>
                        <h5 class="mt-3">
                            {{ number_format($totaux['caisse_cdf'] ?? 0,2,',',' ') }}
                            CDF
                            <br>
                            {{ number_format($totaux['caisse_usd'] ?? 0,2,',',' ') }}
                            USD
                        </h5>
                    </div>

                   {{-- BANQUE --}}
                    <div class="col-md-4 text-center">
                        <span class="badge bg-success">
                            Banque
                        </span>
                        <h5 class="mt-3">
                            {{ number_format($totaux['banque_cdf'] ?? 0,2,',',' ') }}
                            CDF
                            <br>
                            {{ number_format($totaux['banque_usd'] ?? 0,2,',',' ') }}
                            USD
                        </h5>
                    </div>
                    {{-- MOBILE MONEY --}}
                    <div class="col-md-4 text-center">
                        <span class="badge bg-warning text-dark">
                            Mobile Money
                        </span>
                        <h5 class="mt-3">
                            {{ number_format($totaux['mobile_cdf'] ?? 0,2,',',' ') }}
                            CDF
                            <br>
                            {{ number_format($totaux['mobile_usd'] ?? 0,2,',' ,' ') }}
                            USD
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
    </div>
</div>
@endsection