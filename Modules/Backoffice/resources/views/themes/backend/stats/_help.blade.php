<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        La page <strong>Statistiques</strong> vous offre une vue d'ensemble détaillée de l'activité de votre application :
        visiteurs, inscriptions, contenu créé et <strong>tendances dans le temps</strong>.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="bar-chart-3" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Métriques disponibles') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="users" class="text-primary flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Utilisateurs') }}</strong> – {{ __('total, actifs, nouveaux inscrits') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="file-text" class="text-info flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Contenu') }}</strong> – {{ __('articles publiés, commentaires') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="mail" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Newsletter') }}</strong> – {{ __('abonnés actifs') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="activity" class="text-warning flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Activité') }}</strong> – {{ __('actions réalisées dans l\'application') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="calendar" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Périodes et graphiques') }}
    </h6>
    <p class="text-muted small mb-0">
        Filtrez les données sur <strong>7 jours</strong>, <strong>30 jours</strong> ou <strong>90 jours</strong>.
        Les graphiques montrent les tendances quotidiennes pour détecter les pics d'activité et les anomalies.
    </p>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="zap" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        Les statistiques sont calculées <strong>{{ __('en temps réel') }}</strong> depuis la base de données.
        Pour des analyses plus poussées, connectez un outil comme Google Analytics ou Plausible.
    </p>
</div>
