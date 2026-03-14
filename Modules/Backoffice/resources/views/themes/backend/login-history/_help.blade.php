<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        L\'<strong>historique de connexion</strong> enregistre toutes les tentatives d'accès à l'administration :
        succès, échecs, adresses IP et navigateurs utilisés. Un outil essentiel pour détecter les intrusions.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="database" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Informations enregistrées') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">IP</span>
            <p class="text-muted small mb-0">{{ __('Adresse IP de l\'appareil ayant effectué la tentative de connexion.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Navigateur') }}</span>
            <p class="text-muted small mb-0">{{ __('User-agent du navigateur ou de l\'application utilisée.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-secondary mt-1">{{ __('Date') }}</span>
            <p class="text-muted small mb-0">{{ __('Horodatage précis de chaque tentative de connexion.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Statut') }}</span>
            <p class="text-muted small mb-0">{{ __('Succès (connexion réussie) ou Échec (mauvais mot de passe, compte inconnu).') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield-alert" class="text-danger" style="width:16px;height:16px;"></i>
        {{ __('Pourquoi c\'est utile ?') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Détection') }}</strong> {{ __('de connexions depuis des adresses IP inconnues ou étrangères.') }}</li>
        <li class="mb-1"><strong>{{ __('Alerte') }}</strong> {{ __('en cas de nombreuses tentatives échouées sur un même compte (force brute).') }}</li>
        <li class="mb-1"><strong>{{ __('Audit') }}</strong> {{ __('de conformité : traçabilité des accès à l\'administration.') }}</li>
        <li><strong>{{ __('Forensique') }}</strong> {{ __('en cas d\'incident de sécurité.') }}</li>
    </ul>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="eye" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Signes suspects à surveiller') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">!</span>
            <p class="text-muted small mb-0">{{ __('Nombreux échecs consécutifs sur un même email (tentative de force brute).') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">!</span>
            <p class="text-muted small mb-0">{{ __('Connexion réussie depuis une IP ou un pays inhabituel.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">!</span>
            <p class="text-muted small mb-0">{{ __('Connexion à des heures inhabituelles (nuit, week-end).') }}</p>
        </div>
    </div>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Les échecs') }}</strong> {{ __('sont aussi enregistrés, même si le compte n\'existe pas.') }}</li>
        <li class="mb-1"><strong>{{ __('IPs suspectes') }}</strong> {{ __('peuvent être bloquées depuis la page IPs bloquées.') }}</li>
        <li><strong>{{ __('Durée de conservation') }}</strong> {{ __('configurée dans les paramètres de rétention des données.') }}</li>
    </ul>
</div>
