@extends('layouts.app')


@section('content')
@php $isSuperAdmin = auth()->user()?->hasRole('Super Admin') ?? false; @endphp


<div class="container-fluid py-4">


    <div class="card shadow-sm border-0">


        {{-- HEADER --}}

        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">


            <h5 class="mb-0">

                <i class="bi bi-journal-text me-2"></i>

                Types de journaux comptables

            </h5>



            <a href="{{ route('parametres.journal-types.create') }}"
               class="btn btn-primary btn-sm">


                <i class="bi bi-plus-circle me-1"></i>

                Nouveau journal


            </a>


        </div>




        <div class="card-body">



            {{-- MESSAGE SUCCESS --}}

            @if(session('success'))

                <div class="alert alert-success alert-dismissible fade show">


                    <i class="bi bi-check-circle me-2"></i>

                    {{ session('success') }}


                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">

                    </button>


                </div>


            @endif






            <div class="table-responsive">


                <table class="table table-bordered table-hover align-middle">


                    <thead class="table-dark">


                        <tr>


                            <th width="10%">
                                Code
                            </th>


                            <th>
                                Libellé
                            </th>


                            <th>
                                Compte associé
                            </th>


                            <th>
                                Nature
                            </th>

                            <th>Monnaie</th>


                            <th class="text-center">
                                Trésorerie
                            </th>

                            @if($isSuperAdmin)
                                <th>Utilisateur</th>
                            @endif


                            <th width="15%"
                                class="text-center">

                                Actions

                            </th>


                        </tr>


                    </thead>





                    <tbody>


                    @forelse($journalTypes as $journal)



                        <tr>


                            <td class="fw-bold">

                                {{ $journal->code }}

                            </td>





                            <td>

                                {{ $journal->libelle }}

                            </td>





                            <td>


                                @if($journal->compte)


                                    <span class="badge bg-secondary">

                                        {{ $journal->compte->compte }}

                                    </span>


                                    <br>


                                    <small class="text-muted">

                                        {{ $journal->compte->designation }}

                                    </small>


                                @else

                                    <span class="text-muted">

                                        Aucun compte

                                    </span>


                                @endif



                            </td>





                            <td>


                                @switch($journal->nature)


                                    @case('caisse')

                                        <span class="badge bg-success">

                                            Caisse

                                        </span>

                                    @break



                                    @case('banque')

                                        <span class="badge bg-primary">

                                            Banque

                                        </span>

                                    @break



                                    @case('mobile_money')

                                        <span class="badge bg-warning text-dark">

                                            Mobile Money

                                        </span>

                                    @break



                                    @case('achat')

                                        <span class="badge bg-danger">

                                            Achat

                                        </span>

                                    @break



                                    @case('vente')

                                        <span class="badge bg-info">

                                            Vente

                                        </span>

                                    @break



                                    @default

                                        <span class="badge bg-dark">

                                            Opérations diverses

                                        </span>


                                @endswitch



                            </td>





                            <td><span class="badge bg-info text-dark">{{ $journal->monnaie ?? 'CDF' }}</span></td>

                            <td class="text-center">


                                @if($journal->est_tresorerie)


                                    <span class="badge bg-success">

                                        Oui

                                    </span>


                                @else


                                    <span class="badge bg-secondary">

                                        Non

                                    </span>


                                @endif



                            </td>

                            @if($isSuperAdmin)
                                <td>{{ trim(($journal->user?->prenom ?? '').' '.($journal->user?->nom ?? '')) ?: 'Système' }}</td>
                            @endif







                            <td class="text-center">


                                <a href="{{ route('parametres.journal-types.edit',$journal->id) }}"
                                   class="btn btn-warning btn-sm">


                                    <i class="bi bi-pencil-square"></i>


                                </a>






                                <form action="{{ route('parametres.journal-types.destroy',$journal->id) }}"
                                      method="POST"
                                      class="d-inline">


                                    @csrf

                                    @method('DELETE')



                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            data-confirm="Supprimer ce journal ?">


                                        <i class="bi bi-trash"></i>


                                    </button>


                                </form>



                            </td>




                        </tr>



                    @empty



                        <tr>


                            <td colspan="{{ $isSuperAdmin ? 8 : 7 }}"
                                class="text-center text-muted py-4">


                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>


                                Aucun type de journal enregistré.



                            </td>


                        </tr>



                    @endforelse



                    </tbody>



                </table>



            </div>

            {{ $journalTypes->withQueryString()->links() }}



        </div>


    </div>


</div>


@endsection
