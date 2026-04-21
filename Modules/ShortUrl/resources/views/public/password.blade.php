<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lien protégé — laveille.ai</title>
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%}
        body{font-family:-apple-system,BlinkMacSystemFont,'Source Sans Pro',sans-serif;background:#f8fafb;color:#1a1a2e;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:24px 16px;-webkit-font-smoothing:antialiased}
        .wrapper{display:flex;flex-direction:column;align-items:center;width:100%;max-width:440px}
        .logo-link{display:inline-flex;margin-bottom:32px;text-decoration:none}
        .logo-link img{max-height:36px;width:auto}
        .card{width:100%;background:#fff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.04),0 4px 12px rgba(0,0,0,.06);padding:40px 36px 36px}
        .lock-icon{display:flex;align-items:center;justify-content:center;width:56px;height:56px;background:rgba(11,114,133,.08);border-radius:14px;margin:0 auto 24px}
        .lock-icon svg{width:26px;height:26px}
        .card h2{text-align:center;font-size:22px;font-weight:700;color:#1a1a2e;margin-bottom:8px}
        .card .subtitle{text-align:center;font-size:15px;color:#6b7280;margin-bottom:28px;line-height:1.5}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;font-size:14px;font-weight:600;color:#374151;margin-bottom:7px}
        .form-group input[type="password"]{width:100%;padding:12px 14px;font-size:15px;font-family:inherit;color:#1a1a2e;background:#f9fafb;border:1.5px solid #d1d5db;border-radius:8px;outline:none;transition:border-color .2s,box-shadow .2s,background .2s}
        .form-group input[type="password"]::placeholder{color:#6b7280}
        .form-group input[type="password"]:focus{border-color:#0B7285;box-shadow:0 0 0 3px rgba(11,114,133,.12);background:#fff}
        .form-group input.is-invalid{border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.08)}
        .error-message{display:flex;align-items:flex-start;gap:6px;margin-top:8px;font-size:13px;color:#dc2626;line-height:1.4}
        .error-message svg{flex-shrink:0;width:15px;height:15px;margin-top:1px}
        .btn-submit{display:block;width:100%;padding:13px 20px;font-size:15px;font-weight:600;font-family:inherit;color:#fff;background:#0B7285;border:none;border-radius:8px;cursor:pointer;transition:background .2s,box-shadow .2s,transform .1s}
        .btn-submit:hover{background:#095c6a;box-shadow:0 2px 8px rgba(11,114,133,.25)}
        .btn-submit:active{transform:scale(.985)}
        .btn-submit:focus-visible{outline:2px solid #0B7285;outline-offset:2px}
        .promo{margin-top:24px;text-align:center;font-size:13.5px;color:#6b7280;line-height:1.55;padding:0 8px}
        .promo a{color:#0B7285;text-decoration:none;font-weight:600;transition:color .15s}
        .promo a:hover{color:#095c6a;text-decoration:underline}
        footer{margin-top:auto;padding-top:40px;text-align:center;font-size:13px;color:#b0b8c1}
        footer a{color:#b0b8c1;text-decoration:none;transition:color .15s}
        footer a:hover{color:#0B7285}
        @media(max-width:480px){.card{padding:32px 24px 28px;border-radius:12px}.card h2{font-size:20px}.lock-icon{width:50px;height:50px;border-radius:12px;margin-bottom:20px}.lock-icon svg{width:23px;height:23px}}
    </style>
</head>
<body>
    <div class="wrapper">
        <a href="/" class="logo-link">
            <img src="https://laveille.ai/images/logo-horizontal.svg" alt="laveille.ai">
        </a>
        <div class="card">
            <div class="lock-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#0B7285" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    <circle cx="12" cy="16.5" r="1.5" fill="#0B7285" stroke="none"/>
                </svg>
            </div>
            <h2>Lien protégé</h2>
            <p class="subtitle">Ce lien est protégé par un mot de passe.</p>
            <form method="POST" action="{{ route('short-url.password', $slug) }}">
                @csrf
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Entrez le mot de passe" required autofocus autocomplete="off" class="@error('password') is-invalid @enderror">
                    @error('password')
                        <div class="error-message">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>
                <button type="submit" class="btn-submit">Accéder</button>
            </form>
        </div>
        <p class="promo">Vous aussi, créez vos liens courts gratuitement sur <a href="/raccourcir">laveille.ai</a></p>
    </div>
    <footer>&copy; {{ date('Y') }} La veille — <a href="https://laveille.ai">laveille.ai</a></footer>
</body>
</html>
