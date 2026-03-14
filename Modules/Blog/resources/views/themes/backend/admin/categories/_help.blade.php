<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Les') }} <strong>{{ __('catégories') }}</strong> {{ __('permettent d\'organiser vos articles par thématiques. Elles aident vos lecteurs à naviguer dans votre contenu et améliorent le référencement naturel de votre blog.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="folder-tree" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Pourquoi créer des catégories ?') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Les catégories structurent votre blog comme des rayons dans une bibliothèque. Un visiteur intéressé par un sujet précis peut facilement trouver tous les articles liés. De plus, les moteurs de recherche comprennent mieux votre site lorsque le contenu est bien organisé.') }}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('Créez vos catégories en leur donnant un nom, une description et un slug (URL).') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('Un article peut appartenir à') }} <strong>{{ __('une ou plusieurs catégories') }}</strong> {{ __('à la fois.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('Les catégories apparaissent dans les menus et les pages de liste de votre blog.') }}</p>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment créer une catégorie ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Nouvelle catégorie') }}</strong> {{ __('en haut à droite.') }}</li>
        <li class="mb-1">{{ __('Donnez-lui un nom clair et mémorable.') }}</li>
        <li class="mb-1">{{ __('Ajoutez une description pour le SEO (optionnel mais recommandé).') }}</li>
        <li>{{ __('Enregistrez. Elle sera immédiatement disponible lors de la création d\'articles.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="zap" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Limitez-vous à') }} <strong>{{ __('5 à 10 catégories principales') }}</strong> {{ __('pour rester clair et cohérent. Trop de catégories dilue la navigation et nuit au SEO. Préférez des catégories larges, complétées par des tags pour le détail.') }}
    </p>
</div>
