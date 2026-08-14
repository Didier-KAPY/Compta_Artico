@extends('layouts.app')


@section('content')


<div class="container-fluid py-4">


    <div class="card shadow-sm border-0">



        {{-- HEADER --}}

        <div class="card-header bg-dark text-white">


            <h5 class="mb-0">


                <i class="bi bi-journal-plus me-2"></i>


                Nouveau journal comptable


            </h5>


        </div>





        <div class="card-body">



            <form method="POST"
                  action="{{ route('parametres.journal-types.store') }}">


                @csrf





                <div class="row g-3">





                    {{-- CODE JOURNAL --}}


                    <div class="col-md-4">


                        <label class="form-label fw-bold">

                            Code journal

                        </label>



                        <input type="text"
                               name="code"
                               class="form-control @error('code') is-invalid @enderror"
                               value="{{ old('code') }}"
                               placeholder="Ex : CAI">



                        @error('code')

                            <div class="invalid-feedback">

                                {{ $message }}

                            </div>

                        @enderror


                    </div>







                    {{-- LIBELLE --}}


                    <div class="col-md-8">


                        <label class="form-label fw-bold">

                            Libellé du journal

                        </label>



                        <input type="text"
                               name="libelle"
                               class="form-control @error('libelle') is-invalid @enderror"
                               value="{{ old('libelle') }}"
                               placeholder="Ex : Journal Caisse CDF">



                        @error('libelle')

                            <div class="invalid-feedback">

                                {{ $message }}

                            </div>

                        @enderror


                    </div>







                    {{-- COMPTE ASSOCIE --}}


                    <div class="col-md-6">


                        <label class="form-label fw-bold">

                            Compte comptable associé

                        </label>




                        <select name="liste_des_comptes_id"
                                class="form-select @error('liste_des_comptes_id') is-invalid @enderror">



                            <option value="">

                                -- Choisir un compte --

                            </option>




                            @foreach($comptes as $compte)



                                <option value="{{ $compte->id }}"


                                    {{ old('liste_des_comptes_id') == $compte->id ? 'selected' : '' }}>


                                    {{ $compte->compte }}

                                    -

                                    {{ $compte->designation }}



                                </option>



                            @endforeach



                        </select>




                        @error('liste_des_comptes_id')

                            <div class="invalid-feedback">

                                {{ $message }}

                            </div>

                        @enderror



                    </div>








                    {{-- NATURE JOURNAL --}}



                    <div class="col-md-6">


                        <label class="form-label fw-bold">

                            Nature du journal

                        </label>




                        <select name="nature"
                                class="form-select @error('nature') is-invalid @enderror">



                            <option value="">

                                -- Sélectionner --

                            </option>




                            <option value="caisse"
                            {{ old('nature') == 'caisse' ? 'selected':'' }}>


                                Caisse


                            </option>




                            <option value="banque"
                            {{ old('nature') == 'banque' ? 'selected':'' }}>


                                Banque


                            </option>




                            <option value="mobile_money"
                            {{ old('nature') == 'mobile_money' ? 'selected':'' }}>


                                Mobile Money


                            </option>




                            <option value="achat"
                            {{ old('nature') == 'achat' ? 'selected':'' }}>


                                Achat


                            </option>




                            <option value="vente"
                            {{ old('nature') == 'vente' ? 'selected':'' }}>


                                Vente


                            </option>




                            <option value="od"
                            {{ old('nature') == 'od' ? 'selected':'' }}>


                                Opérations diverses


                            </option>



                        </select>




                        @error('nature')

                            <div class="invalid-feedback">

                                {{ $message }}

                            </div>

                        @enderror



                    </div>









                    {{-- MONNAIE --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Monnaie du journal</label>
                        <select name="monnaie" class="form-select @error('monnaie') is-invalid @enderror" required>
                            <option value="CDF" {{ old('monnaie', 'CDF') === 'CDF' ? 'selected' : '' }}>CDF</option>
                            <option value="USD" {{ old('monnaie') === 'USD' ? 'selected' : '' }}>USD</option>
                        </select>
                        @error('monnaie')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- TRESORERIE --}}


                    <div class="col-md-6">


                        <div class="card bg-light border-0 mt-2">


                            <div class="card-body">


                                <div class="form-check form-switch">



                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="est_tresorerie"
                                           value="1"
                                           id="tresorerie"



                                           {{ old('est_tresorerie',false) ? 'checked' : '' }}>



                                    <label class="form-check-label fw-bold"
                                           for="tresorerie">


                                        Journal de trésorerie


                                    </label>



                                </div>


                            </div>


                        </div>


                    </div>





                </div>








                {{-- BOUTONS --}}



                <div class="mt-4 d-flex gap-2">



                    <button type="submit"
                            class="btn btn-primary">


                        <i class="bi bi-check-circle me-1"></i>


                        Enregistrer


                    </button>






                    <a href="{{ route('parametres.journal-types') }}"
                       class="btn btn-secondary">


                        <i class="bi bi-arrow-left me-1"></i>


                        Retour


                    </a>



                </div>





            </form>



        </div>



    </div>



</div>
{{-- LISTE DES JOURNAUX TYPES --}}

<div class="card shadow-sm border-0 mt-4">


<div class="card-header bg-success text-white py-2">

<h6 class="mb-0">

<i class="bi bi-journal-bookmark"></i>

Journaux comptables disponibles

</h6>

</div>



<div class="card-body p-0">


<div class="table-responsive">


<table class="table table-hover table-sm mb-0">


<thead class="table-light">

<tr>

<th>
Code
</th>

<th>
Journal
</th>

<th>
Compte lié
</th>

<th>
Type
</th>

</tr>

</thead>



<tbody>


@forelse($journalTypes as $journal)


<tr>


<td>

{{ $journal->code }}

</td>



<td>

<strong>

{{ $journal->libelle }}

</strong>

</td>



<td>


@if($journal->compte)

{{ $journal->compte->compte }}

-

{{ $journal->compte->designation }}


@else

<span class="text-danger">

Aucun compte lié

</span>


@endif


</td>



<td>


@if($journal->est_tresorerie)

<span class="badge bg-success">

Trésorerie

</span>


@else

<span class="badge bg-secondary">

Général

</span>


@endif


</td>


</tr>


@empty


<tr>

<td colspan="4" class="text-center text-muted">

Aucun journal configuré

</td>

</tr>


@endforelse


</tbody>


</table>


</div>

<div class="p-3">{{ $journalTypes->withQueryString()->links() }}</div>


</div>


</div>


@endsection
