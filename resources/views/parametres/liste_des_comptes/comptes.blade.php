@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-journal-bookmark me-2"></i>
                Liste des comptes
            </h4>

            <small class="text-muted">
                Plan comptable de l'entreprise
            </small>
        </div>

        <a href="{{ url()->previous() }}"
           class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>
            Retour
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm border-0">

        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">

            <span>
                <i class="bi bi-list-ul me-2"></i>
                Comptes enregistrés
            </span>

            <a href="{{ route('parametres.comptes.create') }}"
               class="btn btn-sm btn-light">
                <i class="bi bi-plus-circle"></i>
                Nouveau compte
            </a>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Compte</th>
                            <th>Désignation</th>
                            <th>Nature</th>
                            <th>Observation</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($comptes as $compte)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong>
                                        {{ $compte->compte }}
                                    </strong>
                                </td>
                                <td>
                                    {{ $compte->designation }}
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $compte->nature }}
                                    </span>
                                </td>
                                <td>
                                    {{ $compte->observation }}
                                </td>
                                <td>
                                    <a href="{{ route('parametres.comptes.edit',$compte->id) }}"
                                    class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('parametres.comptes.destroy',$compte->id) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Voulez-vous supprimer ce compte ?')">

                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-danger">

                                            <i class="bi bi-trash"></i>

                                        </button>

                                    </form>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Aucun compte enregistré.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $comptes->links() }}
            </div>
    </div>

</div>

@endsection