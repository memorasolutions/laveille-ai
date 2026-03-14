<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Les <strong>modèles d\'emails</strong> permettent de personnaliser le contenu des emails automatiques
        envoyés par votre application : apparence, texte, variables dynamiques, tout est configurable.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mail" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Exemples d\'emails automatiques') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Bienvenue') }}</span>
            <p class="text-muted small mb-0">{{ __('Envoyé lors de l\'inscription d\'un nouvel utilisateur.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('Mot de passe') }}</span>
            <p class="text-muted small mb-0">{{ __('Email de réinitialisation du mot de passe.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Notification') }}</span>
            <p class="text-muted small mb-0">{{ __('Alertes automatiques déclenchées par des événements de l\'application.') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="code" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Variables dynamiques') }}
    </h6>
    <p class="text-muted small mb-2">{{ __('Insérez des variables dans vos modèles pour personnaliser chaque email :') }}</p>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><code>{name}</code> – {{ __('Nom complet du destinataire') }}</li>
        <li class="mb-1"><code>{email}</code> – {{ __('Adresse email du destinataire') }}</li>
        <li class="mb-1"><code>{app_name}</code> – {{ __('Nom de votre application') }}</li>
        <li><code>{url}</code> – {{ __('Lien d\'action (confirmation, réinitialisation...)') }}</li>
    </ul>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment modifier un modèle ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Modifier') }}</strong> {{ __('à côté du template souhaité.') }}</li>
        <li class="mb-1">{{ __('Editez le sujet et le corps de l\'email dans l\'éditeur.') }}</li>
        <li class="mb-1">{{ __('Enregistrez puis') }} <strong>{{ __('envoyez-vous un email de test') }}</strong> {{ __('pour vérifier le résultat.') }}</li>
        <li>{{ __('Activez ou désactivez un template via le champ Statut.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Testez') }}</strong> {{ __('toujours vos modifications avant de les mettre en production.') }}</li>
        <li class="mb-1"><strong>{{ __('Template inactif') }}</strong> {{ __('l\'application utilisera le template par défaut du code.') }}</li>
        <li><strong>{{ __('Slug') }}</strong> {{ __('est l\'identifiant technique, ne le modifiez pas sans connaître l\'impact.') }}</li>
    </ul>
</div>
