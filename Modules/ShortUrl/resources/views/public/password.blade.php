<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lien protégé</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card shadow-sm" style="max-width:400px;width:100%;">
        <div class="card-body p-4 text-center">
            <div class="mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="text-muted">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h5 class="fw-semibold mb-1">Lien protégé</h5>
            <p class="text-muted small mb-4">Ce lien nécessite un mot de passe pour y accéder.</p>

            <form method="POST" action="{{ route('short-url.password', $slug) }}">
                @csrf
                <div class="mb-3">
                    <label for="password" class="form-label visually-hidden">Mot de passe</label>
                    <input type="password" name="password" id="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Mot de passe" required autofocus>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-100">Accéder</button>
            </form>
        </div>
    </div>
</body>
</html>
