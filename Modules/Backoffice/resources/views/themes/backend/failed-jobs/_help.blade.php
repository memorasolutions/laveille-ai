<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Les <strong>tâches échouées</strong> (failed jobs) sont des opérations en arrière-plan qui n'ont pas
        pu s'exécuter correctement. Elles sont conservées ici pour que vous puissiez les analyser et les relancer.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layers" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Exemples de tâches') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Emails') }}</span>
            <p class="text-muted small mb-0">{{ __('Envoi d\'un email de bienvenue ou d\'une notification.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Rapports') }}</span>
            <p class="text-muted small mb-0">{{ __('Génération de PDF, exports CSV, rapports statistiques.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-secondary mt-1">{{ __('Synchro') }}</span>
            <p class="text-muted small mb-0">{{ __('Synchronisation avec des services externes (API tierce, webhooks...).') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Que faire ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Lisez le message d\'exception pour comprendre la cause de l\'échec.') }}</li>
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Réessayer') }}</strong> {{ __('pour relancer la tâche une fois le problème corrigé.') }}</li>
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Supprimer') }}</strong> {{ __('si la tâche n\'est plus pertinente.') }}</li>
        <li>{{ __('Consultez les logs pour obtenir plus de détails sur l\'erreur.') }}</li>
    </ol>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Normal') }}</span>
            <p class="text-muted small mb-0">{{ __('Des échecs occasionnels sont normaux (coupure réseau temporaire, service externe indisponible).') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Alerte') }}</span>
            <p class="text-muted small mb-0">{{ __('Des échecs répétitifs sur le même job signalent un problème structurel à corriger dans le code.') }}</p>
        </div>
    </div>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Worker de queue') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Pour que les tâches s\'exécutent, le worker de queue doit être actif :') }}
        <code>php artisan queue:work</code>.
        {{ __('En production, utilisez un supervisor pour le maintenir en vie en permanence.') }}
    </p>
</div>
