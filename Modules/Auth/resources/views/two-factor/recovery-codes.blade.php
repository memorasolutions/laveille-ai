@extends('auth::layouts.app')

@section('title', 'Codes de secours 2FA')

@section('content')

<div class="mb-24" style="max-width:600px;">
    <h1 class="fw-semibold mb-4">Codes de secours</h1>
    <p class="text-secondary-light mb-0">Utilisez ces codes si vous n'avez plus accès à votre application authenticator.</p>
</div>

<div class="card mb-20" style="max-width:600px;">
    <div class="card-header">
        <h5 class="card-title fw-semibold text-lg mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:shield-check-outline" class="text-primary-600"></iconify-icon>
            Vos codes de secours
        </h5>
    </div>
    <div class="card-body">
        <p class="text-secondary-light text-sm mb-16">
            Chaque code peut être utilisé une seule fois. Conservez-les dans un gestionnaire de mots de passe.
        </p>

        <div class="row gy-8 mb-20">
            @foreach($recoveryCodes as $code)
            <div class="col-6">
                <div class="px-16 py-10 rounded border text-center font-monospace fw-semibold text-sm"
                     style="background:#f8fafc; user-select:all;">
                    {{ $code }}
                </div>
            </div>
            @endforeach
        </div>

        <div class="border-top pt-16">
            <div class="alert alert-warning d-flex align-items-start gap-8 mb-16">
                <iconify-icon icon="solar:danger-triangle-outline" class="flex-shrink-0 mt-2"></iconify-icon>
                <p class="mb-0 text-sm">La régénération annulera tous les codes actuels. Mettez à jour vos sauvegardes.</p>
            </div>
            <form action="{{ route('user.two-factor.regenerate') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-warning radius-8">
                    <iconify-icon icon="solar:refresh-circle-outline"></iconify-icon>
                    Régénérer les codes
                </button>
            </form>
        </div>
    </div>
</div>

<a href="{{ route('user.profile') }}" class="btn btn-outline-secondary radius-8">
    <iconify-icon icon="solar:arrow-left-outline"></iconify-icon>
    Retour au profil
</a>

@endsection
