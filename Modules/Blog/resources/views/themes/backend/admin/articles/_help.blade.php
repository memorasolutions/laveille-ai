<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('La section') }} <strong>{{ __('Articles') }}</strong> {{ __('vous permet de gérer tout le contenu éditorial de votre blog : créer de nouveaux articles, les modifier, les publier ou planifier leur publication.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layers" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités disponibles') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Créer') }}</strong> {{ __('un article avec éditeur riche (TipTap), image de couverture et métadonnées SEO.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Publier ou planifier') }}</strong> {{ __('la publication à une date et heure précises.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Catégoriser et taguer') }}</strong> {{ __('pour une meilleure organisation et un meilleur référencement.') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Statuts des articles') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-secondary mt-1">{{ __('Brouillon') }}</span>
            <div>
                <strong class="small">{{ __('En préparation') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('L\'article n\'est pas visible sur le site. Vous pouvez le modifier librement.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Publié') }}</span>
            <div>
                <strong class="small">{{ __('Visible sur le site') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('L\'article est accessible à tous les visiteurs.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Planifié') }}</span>
            <div>
                <strong class="small">{{ __('Publication différée') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('L\'article sera publié automatiquement à la date et heure choisies.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Nouvel article') }}</strong> {{ __('pour commencer la rédaction.') }}</li>
        <li class="mb-1">{{ __('Rédigez votre contenu avec l\'éditeur riche, ajoutez une image de couverture et renseignez les métadonnées SEO.') }}</li>
        <li class="mb-1">{{ __('Assignez des catégories et des tags pour organiser votre contenu.') }}</li>
        <li>{{ __('Publiez immédiatement ou planifiez la publication à une date ultérieure.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="zap" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Utilisez les') }} <strong>{{ __('catégories') }}</strong> {{ __('pour les thématiques principales et les') }} <strong>{{ __('tags') }}</strong> {{ __('pour les mots-clés transversaux. Cette combinaison améliore significativement le SEO et la navigation de vos lecteurs.') }}
    </p>
</div>
