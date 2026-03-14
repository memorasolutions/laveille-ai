<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Les') }} <strong>{{ __('redirections d\'URL') }}</strong> {{ __('évitent les erreurs 404 en') }}
        <strong>{{ __('renvoyant automatiquement') }}</strong> {{ __('les visiteurs et les moteurs de recherche vers la nouvelle adresse d\'une page.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="arrow-right-left" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Types de redirections') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">301</span>
            <div>
                <strong class="small">{{ __('Redirection permanente') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Le moteur de recherche transfère le jus SEO vers la nouvelle URL. À utiliser pour les changements définitifs.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">302</span>
            <div>
                <strong class="small">{{ __('Redirection temporaire') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('L\'ancienne URL est conservée dans l\'index. À utiliser pour une maintenance ou une promotion ponctuelle.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Quand utiliser une redirection ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('SEO') }}</span>
            <div>
                <strong class="small">{{ __('Changement d\'URL') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Vous avez renommé une page ou modifié son slug — redirigez l\'ancienne adresse.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('404') }}</span>
            <div>
                <strong class="small">{{ __('Suppression de page') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('La page a été retirée, pointez vers le contenu le plus proche ou vers l\'accueil.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Migration') }}</span>
            <div>
                <strong class="small">{{ __('Migration de contenu') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Déplacez du contenu d\'un ancien domaine vers le nouveau sans perdre votre positionnement.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Les redirections') }} <strong>{{ __('301 préservent le référencement') }}</strong> {{ __('de l\'ancienne URL - privilégiez-les pour les changements permanents.') }}</li>
        <li class="mb-1">{{ __('Utilisez') }} <code>*</code> {{ __('comme wildcard pour rediriger un groupe d\'URLs (ex: /ancien/*).') }}</li>
        <li>{{ __('Le compteur de') }} <strong>{{ __('hits') }}</strong> {{ __('vous indique combien de fois chaque redirection a été déclenchée.') }}</li>
    </ul>
</div>
