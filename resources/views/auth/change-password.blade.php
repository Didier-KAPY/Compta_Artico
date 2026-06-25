<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer mot de passe - Compta i</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #eef2f7, #f8fafc);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .auth-card {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .auth-header {
            background: #0d6efd;
            color: white;
            padding: 25px;
            text-align: center;
        }

        .auth-header i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .auth-body {
            padding: 30px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(13,110,253,.15);
        }

        .btn-primary {
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 12px;
        }

        .footer-text {
            text-align: center;
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 15px;
        }
    </style>
</head>

<body>

<div class="auth-card">

    <!-- HEADER -->
    <div class="auth-header">
        <i class="bi bi-shield-lock-fill"></i>
        <h3 class="mb-0">Sécurité du compte</h3>
        <small>Veuillez définir un nouveau mot de passe</small>
    </div>

    <!-- BODY -->
    <div class="auth-body">

        {{-- SUCCESS --}}
        @if(session('success'))
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        {{-- ERRORS --}}
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FORM --}}
        <form action="{{ route('password.update') }}" method="POST">
            @csrf

            <!-- PASSWORD -->
            <div class="mb-3">
                <label class="form-label">Nouveau mot de passe</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>

            <!-- CONFIRM -->
            <div class="mb-3">
                <label class="form-label">Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" class="form-control" required placeholder="••••••••">
            </div>

            <!-- BUTTON -->
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-lock"></i> Mettre à jour
            </button>
        </form>

        <div class="footer-text mt-3">
            Mot de passe sécurisé requis (8 caractères minimum)
        </div>

    </div>
</div>

</body>
</html>