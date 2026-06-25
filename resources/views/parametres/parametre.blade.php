@extends('layouts.app')

@section('content')

<div class="container py-4">
    <!-- CADRE SYSTEME -->

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-sliders me-2"></i>
            Paramètres du système
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="{{ route('parametres.entreprise') }}"
                       class="btn btn-outline-dark w-100 p-4">
                         <i class="bi bi-building fs-1"></i>
                        <br>
                        Entreprise
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('parametres.comptes') }}"
                       class="btn btn-outline-dark w-100 p-4">
                        <i class="bi bi-journal-bookmark fs-3"></i>
                        <br>
                        Liste des comptes
                    </a>
                </div>
                <div class="col-md-4">
                    <a href=""
                       class="btn btn-outline-dark w-100 p-4">
                        <i class="bi bi-people fs-3"></i>
                        <br>
                        Rôles utilisateurs
                    </a>
                </div>
                <div class="col-md-4">
                    <a href=""
                       class="btn btn-outline-dark w-100 p-4">
                        <i class="bi bi-journal-text fs-3"></i>
                        <br>
                        Types journal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection