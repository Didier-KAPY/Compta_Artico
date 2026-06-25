<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion réussie - Compta i</title>

    {{-- Feuille de style --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #007bff 0%, #00a8ff 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            padding: 40px 60px;
            text-align: center;
            max-width: 450px;
            width: 90%;
            animation: fadeIn 1s ease-in-out;
        }

        .card h1 {
            color: #007bff;
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 1rem;
            margin: 10px 0;
            color: #555;
        }

        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #007bff;
            border-radius: 50%;
            width: 55px;
            height: 55px;
            margin: 25px auto;
            animation: spin 1s linear infinite;
        }

        .checkmark {
            font-size: 60px;
            color: #28a745;
            animation: pop 0.6s ease-out;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pop {
            0% { transform: scale(0.5); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .footer-text {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #777;
        }

        .footer-text a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="checkmark">✅</div>
        <h1>Connexion réussie</h1>

        @php
            $role = Auth::user()->role;
            // Déterminer la route selon le rôle
            if (in_array($role, ['super_admin','admin','manager'])) {
                $redirectRoute = route('dashboard');
                $redirectText = 'tableau de bord';
            } elseif (in_array($role, ['caissier','caissière'])) {
                $redirectRoute = route('caisse.facturesEnAttente');
                $redirectText = 'la caisse';
            } elseif ($role === 'comptable') {
                $redirectRoute = route('fiche.index');
                $redirectText = 'la comptabilité';
            } elseif (in_array($role, ['facturier','facturière'])) {
                $redirectRoute = route('facturations.index');
                $redirectText = 'l’espace facturation';
            } else {
                $redirectRoute = '/';
                $redirectText = 'l’accueil';
            }
        @endphp

        @if (session('success_msg'))
            <p>{{ session('success_msg') }}</p>
        @else
            <p>Bienvenue sur votre espace professionnel.</p>
        @endif

        <div class="loader"></div>
        <p>Redirection vers {{ $redirectText }}...</p>

        <div class="footer-text">
            <p>Vous serez redirigé automatiquement dans quelques secondes.</p>
            <p>Sinon, <a href="{{ $redirectRoute }}">cliquez ici</a>.</p>
        </div>

        {{-- Redirection automatique selon rôle --}}
        <meta http-equiv="refresh" content="3;url={{ $redirectRoute }}">
    </div>
</body>
</html>
