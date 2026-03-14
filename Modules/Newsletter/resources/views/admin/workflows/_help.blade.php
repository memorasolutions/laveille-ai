<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Les') }} <strong>{{ __('workflows') }}</strong> {{ __('sont des automatisations marketing basées sur des déclencheurs. Configurez-les une fois, ils s\'exécutent automatiquement.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="git-branch" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Exemples concrets') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Bienvenue') }}</span>
            <p class="text-muted small mb-0">{{ __('Email de bienvenue automatique après l\'inscription d\'un abonné.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning mt-1">{{ __('Relance') }}</span>
            <p class="text-muted small mb-0">{{ __('Email de relance après 7 jours d\'inactivité.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Série') }}</span>
            <p class="text-muted small mb-0">{{ __('Séquence d\'onboarding en 3 emails sur 2 semaines.') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Déclencheur') }}</strong> {{ __(': l\'événement qui démarre le workflow (inscription, achat, tag...).') }}</li>
        <li class="mb-1"><strong>{{ __('Délai') }}</strong> {{ __(': le temps d\'attente avant l\'action (immédiat, 1 heure, 3 jours...).') }}</li>
        <li class="mb-1"><strong>{{ __('Action') }}</strong> {{ __(': ce qui se passe (envoi email, ajout tag, mise à jour statut...).') }}</li>
        <li>{{ __('Les étapes se chaînent autant que nécessaire.') }}</li>
    </ol>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Les workflows s\'exécutent') }} <strong>{{ __('automatiquement') }}</strong> {{ __('en arrière-plan. Surveillez les statistiques régulièrement : nombre d\'inscrits, emails envoyés, taux d\'ouverture. Un workflow en statut') }} <strong>{{ __('Pause') }}</strong> {{ __('n\'envoie plus mais conserve les inscrits.') }}
    </p>
</div>
