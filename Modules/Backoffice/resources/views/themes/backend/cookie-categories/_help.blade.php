<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('Les <strong>catégories de cookies</strong> organisent les traceurs de votre site par type afin de permettre un <strong>consentement éclairé</strong> de vos visiteurs, comme l\'exige le RGPD.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layers" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Catégories typiques') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Nécessaires') }}</span>
            <p class="text-muted small mb-0">{{ __('Toujours actifs, indispensables au fonctionnement du site. Aucun consentement requis.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Analytics') }}</span>
            <p class="text-muted small mb-0">{{ __('Mesurent les audiences et performances. Consentement requis.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('Marketing') }}</span>
            <p class="text-muted small mb-0">{{ __('Ciblage publicitaire et retargeting. Consentement requis.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-secondary mt-1">{{ __('Préférences') }}</span>
            <p class="text-muted small mb-0">{{ __('Mémorisent les choix de l\'utilisateur (langue, thème). Consentement requis.') }}</p>
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
        <li class="mb-1">{{ __('Créez vos catégories de cookies avec un nom technique et un label affiché au visiteur.') }}</li>
        <li class="mb-1">{!! __('Marquez les catégories <strong>obligatoires</strong> (Nécessaires) pour qu\'elles ne puissent pas être refusées.') !!}</li>
        <li class="mb-1">{!! __('Les catégories actives apparaissent dans la <strong>bannière de consentement</strong> de votre site.') !!}</li>
        <li>{{ __('L\'ordre d\'affichage est contrôlé par le champ Ordre de chaque catégorie.') }}</li>
    </ol>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Pourquoi c\'est obligatoire ?') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{!! __('<strong>RGPD</strong> exige un consentement explicite, séparé par catégorie.') !!}</li>
        <li class="mb-1">{!! __('<strong>Transparence</strong> : les visiteurs doivent savoir à quoi sert chaque cookie.') !!}</li>
        <li class="mb-1">{!! __('<strong>Amende</strong> jusqu\'à 4 % du chiffre d\'affaires mondial en cas de non-conformité.') !!}</li>
        <li>{!! __('<strong>Données anonymisées</strong> ne sont pas concernées par ces règles.') !!}</li>
    </ul>
</div>
