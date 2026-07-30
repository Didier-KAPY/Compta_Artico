<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#12372a">
    <title>Connexion | Compta Artico</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #176b4d;
            --primary-dark: #0f5138;
            --ink: #17251f;
            --muted: #66756e;
            --line: #dce5e0;
            --surface: #ffffff;
            --canvas: #f3f7f5;
            --danger: #b42318;
        }

        * { box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100svh;
            font-family: 'DM Sans', sans-serif;
            color: var(--ink);
            background: var(--canvas);
            -webkit-font-smoothing: antialiased;
        }

        button, input { font: inherit; }

        .login-page {
            min-height: 100vh;
            min-height: 100svh;
            display: grid;
            grid-template-columns: minmax(360px, .92fr) minmax(480px, 1.08fr);
        }

        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: clamp(2rem, 5vw, 4.5rem);
            color: #fff;
            background:
                radial-gradient(circle at 12% 88%, rgba(86, 205, 152, .28), transparent 31%),
                radial-gradient(circle at 92% 10%, rgba(255, 255, 255, .12), transparent 28%),
                linear-gradient(145deg, #0b3d2b 0%, #176b4d 58%, #23855f 100%);
        }

        .brand-panel::before,
        .brand-panel::after {
            content: '';
            position: absolute;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 50%;
        }

        .brand-panel::before { width: 420px; height: 420px; right: -210px; top: 12%; }
        .brand-panel::after { width: 260px; height: 260px; right: -130px; top: calc(12% + 80px); }

        .brand-mark, .brand-content, .brand-footer { position: relative; z-index: 1; }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: .8rem;
            width: fit-content;
            color: #fff;
            text-decoration: none;
            font-family: 'Manrope', sans-serif;
            font-size: 1.12rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .brand-mark__icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 13px;
            background: rgba(255,255,255,.14);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.18);
        }

        .brand-mark__icon img { width: 100%; height: 100%; object-fit: contain; border-radius: 12px; }

        .brand-content { max-width: 560px; margin: 5rem 0; }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.4rem;
            color: #bcebd6;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .eyebrow::before { content: ''; width: 24px; height: 2px; background: #75d3aa; }

        .brand-content h1 {
            margin: 0 0 1.25rem;
            font-family: 'Manrope', sans-serif;
            font-size: clamp(2.2rem, 4.2vw, 4rem);
            line-height: 1.08;
            letter-spacing: -.055em;
        }

        .brand-content p {
            max-width: 510px;
            margin: 0;
            color: rgba(255,255,255,.76);
            font-size: clamp(1rem, 1.4vw, 1.12rem);
            line-height: 1.75;
        }

        .brand-footer { display: flex; align-items: center; gap: .65rem; color: rgba(255,255,255,.62); font-size: .84rem; }
        .brand-footer i { color: #83dbb5; }

        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(2rem, 6vw, 5rem);
            background:
                linear-gradient(rgba(23,107,77,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(23,107,77,.025) 1px, transparent 1px),
                var(--surface);
            background-size: 32px 32px;
        }

        .login-card { width: min(100%, 430px); animation: enter .55s cubic-bezier(.22, 1, .36, 1) both; }

        @keyframes enter {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .mobile-brand { display: none; }

        .login-card h2 {
            margin: 0 0 .65rem;
            font-family: 'Manrope', sans-serif;
            font-size: clamp(1.9rem, 3vw, 2.45rem);
            line-height: 1.2;
            letter-spacing: -.045em;
        }

        .intro { margin: 0 0 2.2rem; color: var(--muted); line-height: 1.6; }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: .7rem;
            margin-bottom: 1.25rem;
            padding: .9rem 1rem;
            border: 1px solid;
            border-radius: 12px;
            font-size: .9rem;
            line-height: 1.45;
        }

        .alert-danger { color: #8a1f17; background: #fff4f2; border-color: #ffd3cf; }
        .alert-success { color: #17603f; background: #effbf5; border-color: #bcebd5; }

        .field { margin-bottom: 1.25rem; }

        .field label {
            display: block;
            margin-bottom: .55rem;
            font-size: .9rem;
            font-weight: 700;
        }

        .input-wrap { position: relative; }

        .input-wrap > i {
            position: absolute;
            left: 1rem;
            top: 50%;
            z-index: 1;
            color: #819087;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            height: 52px;
            padding: 0 3rem 0 2.8rem;
            color: var(--ink);
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .form-control::placeholder { color: #9aa6a0; }
        .form-control:hover { border-color: #b8c8c0; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(23,107,77,.11); }
        .form-control.is-invalid { border-color: var(--danger); }

        .toggle-password {
            position: absolute;
            right: .55rem;
            top: 50%;
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            padding: 0;
            color: #6f7d76;
            background: transparent;
            border: 0;
            border-radius: 9px;
            transform: translateY(-50%);
            cursor: pointer;
        }

        .toggle-password:hover, .toggle-password:focus-visible { color: var(--primary); background: #eef6f2; outline: none; }
        .field-error { margin: .45rem 0 0; color: var(--danger); font-size: .82rem; }

        .submit-button {
            width: 100%;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .65rem;
            margin-top: .35rem;
            color: #fff;
            background: var(--primary);
            border: 0;
            border-radius: 12px;
            box-shadow: 0 10px 24px rgba(23,107,77,.2);
            font-weight: 700;
            cursor: pointer;
            transition: transform .2s, background .2s, box-shadow .2s;
        }

        .submit-button:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 13px 28px rgba(23,107,77,.25); }
        .submit-button:active { transform: translateY(0); }
        .submit-button:focus-visible { outline: 3px solid rgba(23,107,77,.25); outline-offset: 3px; }

        .support {
            margin: 1.75rem 0 0;
            padding-top: 1.5rem;
            color: var(--muted);
            border-top: 1px solid #e7edea;
            text-align: center;
            font-size: .87rem;
        }

        .support strong { color: var(--ink); }

        @media (max-width: 900px) {
            .login-page { grid-template-columns: 1fr; }
            .brand-panel { display: none; }
            .form-panel { min-height: 100vh; min-height: 100svh; padding: 2rem 1.25rem; }
            .mobile-brand { display: flex; align-items: center; gap: .7rem; margin-bottom: 2.5rem; color: var(--primary-dark); font-family: 'Manrope', sans-serif; font-weight: 800; }
            .mobile-brand span:first-child { display: grid; place-items: center; width: 38px; height: 38px; color: #fff; background: var(--primary); border-radius: 11px; }
        }

        @media (max-width: 420px) {
            .form-panel { align-items: flex-start; padding-top: 1.5rem; }
            .login-card h2 { font-size: 1.8rem; }
            .intro { margin-bottom: 1.7rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>
<main class="login-page">
    <section class="brand-panel" aria-label="Présentation de Compta Artico">
        <div class="brand-mark">
            <span class="brand-mark__icon">
                @if(!empty($infos->logo))
                    <img src="{{ asset('storage/' . $infos->logo) }}" alt="">
                @else
                    <i class="bi bi-bar-chart-fill" aria-hidden="true"></i>
                @endif
            </span>
            <span>Compta Artico</span>
        </div>

        <div class="brand-content">
            <span class="eyebrow">Gestion comptable</span>
            <h1>Vos finances,<br>maîtrisées simplement.</h1>
            <p>Centralisez vos opérations, suivez vos flux et pilotez votre activité depuis un espace fiable et sécurisé.</p>
        </div>

        <div class="brand-footer">
            <i class="bi bi-shield-check" aria-hidden="true"></i>
            <span>Accès sécurisé · Données confidentielles</span>
        </div>
    </section>

    <section class="form-panel">
        <div class="login-card">
            <div class="mobile-brand" aria-hidden="true">
                <span><i class="bi bi-bar-chart-fill"></i></span>
                <span>Compta Artico</span>
            </div>

            <h2>Heureux de vous revoir</h2>
            <p class="intro">Connectez-vous pour accéder à votre espace de gestion.</p>

            @if(session('error_msg'))
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                    <span>{{ session('error_msg') }}</span>
                </div>
            @endif

            @if(session('success_msg'))
                <div class="alert alert-success" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>{{ session('success_msg') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('handlelogin') }}">
                @csrf

                <div class="field">
                    <label for="email">Adresse e-mail</label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <input
                            id="email"
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="nom@entreprise.com"
                            autocomplete="email"
                            inputmode="email"
                            aria-describedby="email-error"
                            autofocus
                            required>
                    </div>
                    @error('email')
                        <p class="field-error" id="email-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Mot de passe</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input
                            id="password"
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            name="password"
                            placeholder="Votre mot de passe"
                            autocomplete="current-password"
                            aria-describedby="password-error"
                            required>
                        <button class="toggle-password" type="button" aria-label="Afficher le mot de passe" aria-pressed="false">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="field-error" id="password-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="submit-button">
                    <span>Se connecter</span>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <p class="support">Besoin d'un accès ? <strong>Contactez votre administrateur.</strong></p>
        </div>
    </section>
</main>

<script>
    const toggleButton = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');

    toggleButton?.addEventListener('click', () => {
        const shouldShow = passwordInput.type === 'password';
        passwordInput.type = shouldShow ? 'text' : 'password';
        toggleButton.setAttribute('aria-pressed', String(shouldShow));
        toggleButton.setAttribute('aria-label', shouldShow ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        toggleButton.querySelector('i').className = shouldShow ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
</script>
</body>
</html>
