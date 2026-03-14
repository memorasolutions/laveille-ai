<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        La page <strong>Profil</strong> vous permet de gérer vos <strong>informations personnelles</strong>
        et les paramètres de sécurité de votre compte administrateur.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="user" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Informations personnelles') }}</strong> – {{ __('nom, adresse email') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Mot de passe') }}</strong> – {{ __('changer votre mot de passe actuel') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Sessions actives') }}</strong> – {{ __('voir et révoquer les connexions en cours') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Double authentification (2FA)') }}</strong> – {{ __('sécurité renforcée') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Sécurité') }}
    </h6>
    <p class="text-muted small mb-0">
        Activez l'<strong>{{ __('authentification à deux facteurs (2FA)') }}</strong> pour protéger votre compte
        même si votre mot de passe est compromis. Utilisez Google Authenticator, Authy ou toute application TOTP.
    </p>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        Votre <strong>{{ __('email sert aussi d\'identifiant de connexion') }}</strong>.
        Si vous le modifiez, pensez à retenir la nouvelle adresse pour vos prochaines connexions.
        Un mot de passe fort contient au moins 12 caractères avec des majuscules, chiffres et symboles.
    </p>
</div>
