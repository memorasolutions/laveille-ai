@extends('auth::layouts.app')

@section('title', 'Codes de secours 2FA')

@section('content')

<div class="mb-4" style="max-width:600px;">
    <h1 class="fw-semibold mb-1">Codes de secours</h1>
    <p class="text-muted mb-0">Utilisez ces codes si vous n'avez plus accès à votre application authenticator.</p>
</div>

<div class="card mb-3" style="max-width:600px;">
    <div class="card-header">
        <h5 class="card-title fw-semibold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="shield-check" class="text-primary"></i>
            Vos codes de secours
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted text-sm mb-3">
            Chaque code peut être utilisé une seule fois. Conservez-les dans un gestionnaire de mots de passe.
        </p>

        <div class="row gy-2 mb-3">
            @foreach($recoveryCodes as $code)
            <div class="col-6">
                <div class="px-3 py-2 rounded border text-center font-monospace fw-semibold text-sm"
                     style="background:#f8fafc; user-select:all;">
                    {{ $code }}
                </div>
            </div>
            @endforeach
        </div>

        <div class="border-top pt-3">
            <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
                <i data-lucide="alert-triangle" class="flex-shrink-0 mt-1"></i>
                <p class="mb-0 text-sm">La régénération annulera tous les codes actuels. Mettez à jour vos sauvegardes.</p>
            </div>
            <form action="{{ route('user.two-factor.regenerate') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-warning rounded-2">
                    <i data-lucide="refresh-cw"></i>
                    Régénérer les codes
                </button>
            </form>
        </div>
    </div>
</div>

<a href="{{ route('user.profile') }}" class="btn btn-outline-secondary rounded-2">
    <i data-lucide="arrow-left"></i>
    Retour au profil
</a>

@endsection
