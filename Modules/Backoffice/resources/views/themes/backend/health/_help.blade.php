<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        La <strong>santé système</strong> vérifie en temps réel l'état de tous les composants critiques
        de votre application et vous alerte immédiatement en cas de problème.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="search" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Que vérifie-t-on ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Base de données') }}</span>
            <p class="text-muted small mb-0">{{ __('Connexion et disponibilité de la base de données principale.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Cache') }}</span>
            <p class="text-muted small mb-0">{{ __('Fonctionnement du driver de cache (Redis, Memcached...).') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-secondary mt-1">{{ __('Stockage') }}</span>
            <p class="text-muted small mb-0">{{ __('Espace disque disponible et accès en écriture au stockage.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('Queue') }}</span>
            <p class="text-muted small mb-0">{{ __('Worker de file d\'attente actif pour traiter les tâches en arrière-plan.') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="activity" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Indicateurs') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">OK</span>
            <p class="text-muted small mb-0">{{ __('Le composant fonctionne correctement. Aucune action requise.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('Avertissement') }}</span>
            <p class="text-muted small mb-0">{{ __('Fonctionnel mais à surveiller (ex : disque presque plein).') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Échec') }}</span>
            <p class="text-muted small mb-0">{{ __('Problème critique, l\'application peut être impactée.') }}</p>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment l\'utiliser ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Lancer les vérifications') }}</strong> {{ __('pour obtenir un état en temps réel.') }}</li>
        <li class="mb-1">{{ __('Pour chaque problème, une') }} <strong>{{ __('instruction de correction') }}</strong> {{ __('est fournie.') }}</li>
        <li class="mb-1">{{ __('Certains problèmes peuvent être') }} <strong>{{ __('corrigés automatiquement') }}</strong> {{ __('via le bouton Corriger.') }}</li>
        <li>{{ __('Utilisez le bouton Expliquer pour comprendre la nature du problème.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Que faire si rouge ?') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Consultez') }}</strong> {{ __('les logs de l\'application pour les détails de l\'erreur.') }}</li>
        <li class="mb-1"><strong>{{ __('Suivez') }}</strong> {{ __('les instructions de correction affichées dans le tableau.') }}</li>
        <li><strong>{{ __('Contactez') }}</strong> {{ __('votre administrateur système si le problème persiste.') }}</li>
    </ul>
</div>
