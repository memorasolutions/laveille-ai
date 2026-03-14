<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Les <strong>Paramètres</strong> centralisent toute la <strong>configuration générale</strong> de votre application :
        informations du site, emails, réseaux sociaux, intégrations tierces et options avancées.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layers" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Catégories disponibles') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-primary mt-1">{{ __('Général') }}</span>
            <p class="text-muted small mb-0">{{ __('Nom du site, timezone, langue par défaut') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-info mt-1">{{ __('Emails') }}</span>
            <p class="text-muted small mb-0">{{ __('Expéditeur, serveur SMTP, templates') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-success mt-1">{{ __('Réseaux sociaux') }}</span>
            <p class="text-muted small mb-0">{{ __('Liens vers vos profils sociaux') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-warning mt-1">{{ __('Intégrations') }}</span>
            <p class="text-muted small mb-0">{{ __('APIs tierces, clés, webhooks') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-secondary mt-1">{{ __('Avancé') }}</span>
            <p class="text-muted small mb-0">{{ __('Mode maintenance, cache, débogage') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <p class="text-muted small mb-0">
        Chaque paramètre prend effet <strong>{{ __('immédiatement') }}</strong> après la sauvegarde.
        Les valeurs sont stockées en base de données et mises en cache automatiquement pour les performances.
    </p>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        Certains paramètres nécessitent de <strong>{{ __('vider le cache') }}</strong> pour que les changements soient visibles.
        Utilisez la page <em>Cache</em> du menu Système si vous ne voyez pas l'effet escompté.
    </p>
</div>
