<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#12372a">
    <title>Nouveau mot de passe | Compta Artico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{--primary:#176b4d;--primary-dark:#0f5138;--ink:#17251f;--muted:#66756e;--line:#dce5e0;--canvas:#f3f7f5;--danger:#b42318}
        *{box-sizing:border-box}html,body{min-height:100%}body{margin:0;min-height:100vh;min-height:100svh;font-family:'DM Sans',sans-serif;color:var(--ink);background:var(--canvas);-webkit-font-smoothing:antialiased}button,input{font:inherit}
        .page{min-height:100vh;min-height:100svh;display:grid;grid-template-columns:minmax(340px,.82fr) minmax(500px,1.18fr)}
        .security-panel{position:relative;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden;padding:clamp(2rem,5vw,4.5rem);color:#fff;background:radial-gradient(circle at 12% 88%,rgba(86,205,152,.28),transparent 31%),radial-gradient(circle at 92% 10%,rgba(255,255,255,.12),transparent 28%),linear-gradient(145deg,#0b3d2b,#176b4d 58%,#23855f)}
        .security-panel:after{content:'';position:absolute;width:420px;height:420px;right:-250px;top:18%;border:1px solid rgba(255,255,255,.14);border-radius:50%}
        .brand,.security-copy,.secure-note{position:relative;z-index:1}.brand{display:flex;align-items:center;gap:.75rem;font-family:'Manrope',sans-serif;font-weight:800}.brand-icon{width:42px;height:42px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.28);border-radius:13px;background:rgba(255,255,255,.14)}
        .security-copy{max-width:520px;margin:5rem 0}.security-copy .eyebrow{display:block;margin-bottom:1.2rem;color:#bcebd6;font-size:.78rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase}.security-copy h1{margin:0 0 1.2rem;font-family:'Manrope',sans-serif;font-size:clamp(2.1rem,4vw,3.6rem);line-height:1.1;letter-spacing:-.05em}.security-copy p{margin:0;color:rgba(255,255,255,.76);font-size:1.05rem;line-height:1.7}.secure-note{display:flex;align-items:center;gap:.65rem;color:rgba(255,255,255,.68);font-size:.85rem}.secure-note i{color:#83dbb5}
        .form-panel{display:flex;align-items:center;justify-content:center;padding:clamp(2rem,7vw,6rem);background:linear-gradient(rgba(23,107,77,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(23,107,77,.025) 1px,transparent 1px),#fff;background-size:32px 32px}.card{width:min(100%,460px)}
        .step{display:inline-flex;align-items:center;gap:.5rem;margin-bottom:1.1rem;padding:.4rem .7rem;color:var(--primary-dark);background:#eaf6f0;border-radius:999px;font-size:.78rem;font-weight:700}.card h2{margin:0 0 .65rem;font-family:'Manrope',sans-serif;font-size:clamp(1.9rem,3vw,2.4rem);letter-spacing:-.045em}.intro{margin:0 0 1.8rem;color:var(--muted);line-height:1.6}
        .account{display:flex;align-items:center;gap:.8rem;margin-bottom:1.7rem;padding:.85rem 1rem;border:1px solid #e2e9e5;border-radius:12px;background:#fafcfb}.avatar{width:38px;height:38px;display:grid;place-items:center;color:#fff;background:var(--primary);border-radius:50%;font-weight:700}.account strong,.account small{display:block}.account small{color:var(--muted);margin-top:1px}
        .alert{display:flex;gap:.7rem;margin-bottom:1.25rem;padding:.85rem 1rem;border:1px solid #ffd3cf;border-radius:12px;color:#8a1f17;background:#fff4f2;font-size:.88rem}.alert ul{margin:0;padding-left:1rem}
        .field{margin-bottom:1.15rem}.field label{display:block;margin-bottom:.5rem;font-size:.9rem;font-weight:700}.input-wrap{position:relative}.input-wrap>i{position:absolute;left:1rem;top:50%;color:#819087;transform:translateY(-50%)}.form-control{width:100%;height:52px;padding:0 3rem 0 2.8rem;color:var(--ink);background:#fff;border:1px solid var(--line);border-radius:12px;outline:none;transition:.2s}.form-control:hover{border-color:#b8c8c0}.form-control:focus{border-color:var(--primary);box-shadow:0 0 0 4px rgba(23,107,77,.11)}.form-control.invalid{border-color:var(--danger)}
        .toggle{position:absolute;right:.55rem;top:50%;width:38px;height:38px;display:grid;place-items:center;padding:0;color:#6f7d76;background:transparent;border:0;border-radius:9px;transform:translateY(-50%);cursor:pointer}.toggle:hover,.toggle:focus-visible{color:var(--primary);background:#eef6f2;outline:none}.field-error{margin:.4rem 0 0;color:var(--danger);font-size:.82rem}
        .requirements{display:grid;grid-template-columns:1fr 1fr;gap:.45rem;margin:0 0 1.4rem;padding:0;list-style:none;color:var(--muted);font-size:.8rem}.requirements li{display:flex;align-items:center;gap:.4rem}.requirements i{color:#9aa6a0}.requirements li.valid{color:var(--primary-dark)}.requirements li.valid i{color:var(--primary)}
        .submit{width:100%;height:54px;display:flex;align-items:center;justify-content:center;gap:.65rem;color:#fff;background:var(--primary);border:0;border-radius:12px;box-shadow:0 10px 24px rgba(23,107,77,.2);font-weight:700;cursor:pointer;transition:.2s}.submit:hover{background:var(--primary-dark);transform:translateY(-1px)}.submit:focus-visible{outline:3px solid rgba(23,107,77,.25);outline-offset:3px}
        @media(max-width:880px){.page{grid-template-columns:1fr}.security-panel{display:none}.form-panel{min-height:100vh;min-height:100svh;padding:2rem 1.25rem}.card:before{content:'Compta Artico';display:block;margin-bottom:2rem;color:var(--primary-dark);font-family:'Manrope',sans-serif;font-weight:800}}
        @media(max-width:430px){.requirements{grid-template-columns:1fr}.form-panel{align-items:flex-start}}
        @media(prefers-reduced-motion:reduce){*{transition:none!important}}
    </style>
</head>
<body>
<main class="page">
    <section class="security-panel" aria-label="Sécurité du compte">
        <div class="brand"><span class="brand-icon"><i class="bi bi-bar-chart-fill"></i></span><span>Compta Artico</span></div>
        <div class="security-copy"><span class="eyebrow">Protection du compte</span><h1>Votre sécurité commence ici.</h1><p>Choisissez un mot de passe robuste et unique pour protéger vos données comptables et votre espace de travail.</p></div>
        <div class="secure-note"><i class="bi bi-shield-check"></i><span>Connexion chiffrée · Informations confidentielles</span></div>
    </section>

    <section class="form-panel">
        <div class="card">
            <span class="step"><i class="bi bi-key"></i>Étape de sécurité obligatoire</span>
            <h2>Créez votre mot de passe</h2>
            <p class="intro">Pour continuer, remplacez le mot de passe temporaire de votre compte.</p>

            <div class="account">
                <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->prenom ?? auth()->user()->nom ?? 'U', 0, 1)) }}</span>
                <div><strong>{{ trim((auth()->user()->prenom ?? '').' '.(auth()->user()->nom ?? '')) ?: 'Utilisateur' }}</strong><small>{{ auth()->user()->email }}</small></div>
            </div>

            @if($errors->any())
                <div class="alert" role="alert"><i class="bi bi-exclamation-circle-fill"></i><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form action="{{ route('password.update') }}" method="POST">@csrf
                <div class="field"><label for="password">Nouveau mot de passe</label><div class="input-wrap"><i class="bi bi-lock"></i><input id="password" type="password" name="password" class="form-control @error('password') invalid @enderror" placeholder="Saisissez votre nouveau mot de passe" autocomplete="new-password" minlength="8" required><button class="toggle" type="button" data-target="password" aria-label="Afficher le mot de passe"><i class="bi bi-eye"></i></button></div>@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div class="field"><label for="password_confirmation">Confirmation</label><div class="input-wrap"><i class="bi bi-shield-lock"></i><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" placeholder="Confirmez votre mot de passe" autocomplete="new-password" minlength="8" required><button class="toggle" type="button" data-target="password_confirmation" aria-label="Afficher la confirmation"><i class="bi bi-eye"></i></button></div></div>

                <ul class="requirements" aria-label="Critères du mot de passe"><li data-rule="length"><i class="bi bi-circle"></i>8 caractères minimum</li><li data-rule="upper"><i class="bi bi-circle"></i>Une majuscule</li><li data-rule="number"><i class="bi bi-circle"></i>Un chiffre</li><li data-rule="match"><i class="bi bi-circle"></i>Mots de passe identiques</li></ul>
                <button type="submit" class="submit"><i class="bi bi-shield-check"></i><span>Sécuriser mon compte</span><i class="bi bi-arrow-right"></i></button>
            </form>
        </div>
    </section>
</main>
<script>
    document.querySelectorAll('.toggle').forEach(button => button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.target);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        button.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    }));
    const password = document.getElementById('password');
    const confirmation = document.getElementById('password_confirmation');
    const updateRules = () => {
        const rules = {length: password.value.length >= 8, upper: /[A-ZÀ-ÖØ-Þ]/.test(password.value), number: /\d/.test(password.value), match: password.value.length > 0 && password.value === confirmation.value};
        Object.entries(rules).forEach(([name, valid]) => {const item=document.querySelector(`[data-rule="${name}"]`);item.classList.toggle('valid',valid);item.querySelector('i').className=valid?'bi bi-check-circle-fill':'bi bi-circle';});
    };
    password.addEventListener('input', updateRules); confirmation.addEventListener('input', updateRules);
</script>
</body>
</html>
