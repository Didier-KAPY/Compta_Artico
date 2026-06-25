<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Compta Artico</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            margin: 0;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .auth-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.10);
            width: 100%;
            max-width: 430px;
            overflow: hidden;
            border: 1px solid #eef0f3;
        }

        .auth-header {
            background: #ffffff;
            text-align: center;
            padding: 2rem 1.5rem 1rem;
            border-bottom: 1px solid #eef0f3;
        }

        .auth-header h2 {
            font-weight: 600;
            color: #0d6efd;
            margin-top: 10px;
        }

        .auth-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .logo {
            max-height: 60px;
        }

        .auth-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.75rem;
            border: 1px solid #e3e6ea;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.15rem rgba(13,110,253,.15);
        }

        .btn-primary {
            background: #0d6efd;
            border: none;
            border-radius: 12px;
            padding: 0.75rem;
            font-weight: 600;
            transition: 0.2s ease-in-out;
        }

        .btn-primary:hover {
            background: #0b5ed7;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 12px;
            font-size: 0.9rem;
        }

        .footer-text {
            text-align: center;
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 1.5rem;
        }

        .footer-text span {
            font-weight: 600;
        }
    </style>
</head>

<body>

<div class="auth-card">

    <!-- HEADER -->
    <div class="auth-header">

        @if(!empty($infos->logo))
            <img src="{{ asset('storage/' . $infos->logo) }}" class="logo" alt="Logo">
        @else
            <i class="bi bi-shield-lock-fill fs-1 text-primary"></i>
        @endif

        <h2>Connexion</h2>
        <p>Accédez à votre espace sécurisé</p>
    </div>

    <!-- BODY -->
    <div class="auth-body">

        <form method="POST" action="{{ route('handlelogin') }}">
            @csrf

            <!-- ERROR -->
            @if(session('error_msg'))
                <div class="alert alert-danger text-center">
                    {{ session('error_msg') }}
                </div>
            @endif

            <!-- SUCCESS -->
            @if(session('success_msg'))
                <div class="alert alert-success text-center">
                    {{ session('success_msg') }}
                </div>
            @endif

            <!-- EMAIL -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Adresse e-mail</label>
                <input type="email"
                       class="form-control"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="exemple@entreprise.com"
                       required>
            </div>

            <!-- PASSWORD -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Mot de passe</label>
                <input type="password"
                       class="form-control"
                       name="password"
                       placeholder="••••••••"
                       required>
            </div>

            <!-- BUTTON -->
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    Se connecter
                </button>
            </div>

        </form>

        <p class="footer-text">
            Vous n'avez pas de compte ? <br>
            <span>Contactez l'administrateur</span>
        </p>

    </div>
</div>

</body>
</html>