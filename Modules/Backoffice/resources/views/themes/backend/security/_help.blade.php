<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('Le tableau de bord <strong>Sécurité</strong> surveille les <strong>tentatives de connexion</strong> et les <strong>IPs suspectes</strong> en temps réel. Il vous permet de détecter rapidement des activités anormales et de réagir avant qu\'un problème survienne.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="activity" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Ce que vous surveillez') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('Le tableau affiche les statistiques des <strong>24 dernières heures</strong> : nombre total de connexions, connexions réussies, connexions échouées et IPs actuellement bloquées. Les tentatives suspectes sont celles qui ont échoué plusieurs fois de suite.') !!}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Bonnes pratiques') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Activer') }}</span>
            <div>
                <strong class="small">{{ __('Authentification à deux facteurs (2FA)') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Protégez les comptes administrateurs avec une deuxième couche de vérification.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Surveiller') }}</span>
            <div>
                <strong class="small">{{ __('IPs suspectes') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Une IP avec beaucoup d\'échecs peut indiquer une tentative de force brute.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Bloquer') }}</span>
            <div>
                <strong class="small">{{ __('IPs malveillantes') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Ajoutez les IPs identifiées comme malveillantes dans la section IPs bloquées.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités de sécurité') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Rate limiting') }}</strong> {{ __('- bloque automatiquement après plusieurs tentatives échouées.') }}</li>
        <li class="mb-1"><strong>{{ __('Protection CSRF') }}</strong> {{ __('- tous les formulaires sont protégés contre les attaques cross-site.') }}</li>
        <li><strong>{{ __('Logs de connexion') }}</strong> {{ __('- chaque tentative est enregistrée avec IP et horodatage.') }}</li>
    </ul>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(239,68,68,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-danger" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Consultez régulièrement') }}</strong> {{ __('ce tableau, idéalement chaque semaine.') }}</li>
        <li class="mb-1"><strong>{{ __('Historique complet') }}</strong> {{ __('- retrouvez toutes les tentatives dans la section Historique de connexions.') }}</li>
        <li><strong>{{ __('En cas d\'attaque') }}</strong> {{ __('- bloquez l\'IP immédiatement et changez les mots de passe des comptes ciblés.') }}</li>
    </ul>
</div>
