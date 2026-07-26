@extends('layouts.app')

@section('content')

<div class="container py-4">

    @php
        $role = strtolower(auth()->user()->role?->designation ?? '');
    @endphp


    {{-- CADRE SYSTEME --}}
    <div class="card shadow-sm border-0">

        <div class="card-header bg-primary text-white">

            <i class="bi bi-sliders me-2"></i>

            Paramètres du système

        </div>


        <div class="card-body">


            <div class="row g-3">


                {{-- SUPER ADMIN + ADMIN : TOUT --}}
                @if(in_array($role, [

                    'super admin',
                    'admin'

                ]))


                    {{-- Entreprise --}}
                    <div class="col-md-4">

                        <a href="{{ route('parametres.entreprise') }}"
                           class="btn btn-outline-dark w-100 p-4">

                            <i class="bi bi-building fs-1"></i>

                            <br>

                            Entreprise

                        </a>

                    </div>


                    {{-- Liste des comptes --}}
                    <div class="col-md-4">

                        <a href="{{ route('parametres.comptes') }}"
                           class="btn btn-outline-dark w-100 p-4">

                            <i class="bi bi-journal-bookmark fs-3"></i>

                            <br>

                            Liste des comptes

                        </a>

                    </div>



                    {{-- Types journal --}}
                    <div class="col-md-4">

                        <a href="{{ route('parametres.journal-types.create') }}"
                           class="btn btn-outline-dark w-100 p-4">

                            <i class="bi bi-journal-text fs-3"></i>

                            <br>

                            Types journal

                        </a>

                    </div>



                    {{-- Taux de change --}}
                    <div class="col-md-4">

                        <a href="{{ route('parametres.taux-change.create') }}"
                           class="btn btn-outline-dark w-100 p-4">

                            <i class="bi bi-currency-exchange fs-3"></i>

                            <br>

                            Taux de change

                        </a>

                    </div>



                    {{-- Paramétrage comptable --}}
                    <div class="col-md-4">

                        <a href="{{ route('parametres.comptables.index') }}"
                           class="btn btn-outline-dark w-100 p-4">

                            <i class="bi bi-calculator-fill fs-3"></i>

                            <br>

                            Paramétrage comptable

                        </a>

                    </div>



                    {{-- Rôles --}}
                    <div class="col-md-4">

                        <a href="#"
                           class="btn btn-outline-dark w-100 p-4">

                            <i class="bi bi-people fs-3"></i>

                            <br>

                            Rôles utilisateurs

                        </a>

                    </div>


                @endif






                {{-- DIRECTEUR GENERAL + GERANT : ENTREPRISE UNIQUEMENT --}}
                @if(in_array($role, [

                    'directeur général',
                    'gérant'

                ]))


                    <div class="col-md-4">

                        <a href="{{ route('parametres.entreprise') }}"
                           class="btn btn-outline-dark w-100 p-4">

                            <i class="bi bi-building fs-1"></i>

                            <br>

                            Entreprise

                        </a>

                    </div>


                @endif






                {{-- COMPTABLE + DAF --}}
                @if(in_array($role, [

                    'comptable',
                    'daf'

                ]))


                    {{-- Liste des comptes --}}
                    <div class="col-md-4">

                        <a href="{{ route('parametres.comptes') }}"
                           class="btn btn-outline-dark w-100 p-4">

                            <i class="bi bi-journal-bookmark fs-3"></i>

                            <br>

                            Liste des comptes

                        </a>

                    </div>



                    {{-- Types journal --}}
                    <div class="col-md-4">

                        <a href="{{ route('parametres.journal-types.create') }}"
                           class="btn btn-outline-dark w-100 p-4">

                            <i class="bi bi-journal-text fs-3"></i>

                            <br>

                            Types journal

                        </a>

                    </div>



                    {{-- Taux de change --}}
                    <div class="col-md-4">

                        <a href="{{ route('parametres.taux-change.create') }}"
                           class="btn btn-outline-dark w-100 p-4">

                            <i class="bi bi-currency-exchange fs-3"></i>

                            <br>

                            Taux de change

                        </a>

                    </div>



                    {{-- Paramétrage comptable --}}
                    <div class="col-md-4">

                        <a href="{{ route('parametres.comptables.index') }}"
                           class="btn btn-outline-dark w-100 p-4">

                            <i class="bi bi-calculator-fill fs-3"></i>

                            <br>

                            Paramétrage comptable

                        </a>

                    </div>


                @endif


            </div>


        </div>

    </div>


</div>

@endsection