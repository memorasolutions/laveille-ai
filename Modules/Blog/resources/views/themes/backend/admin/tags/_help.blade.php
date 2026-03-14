<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Les') }} <strong>{{ __('tags') }}</strong> {{ __('sont des étiquettes libres qui permettent d\'affiner le classement de vos articles. Contrairement aux catégories, ils sont transversaux et peuvent relier des articles de thématiques différentes autour d\'un mot-clé commun.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="git-branch" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Différence avec les catégories') }}
    </h6>
    <div class="d-flex flex-column gap-3">
        <div>
            <strong class="small d-block mb-1">{{ __('Catégories') }}</strong>
            <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Hiérarchiques, peu nombreuses, définissent la thématique principale d\'un article. Exemple : "Technologie", "Marketing", "Design".') }}</p>
        </div>
        <div>
            <strong class="small d-block mb-1">{{ __('Tags') }}</strong>
            <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Libres, transversaux, peuvent traverser plusieurs catégories. Exemple : "Laravel", "Performance", "Tutoriel".') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('Un article peut avoir') }} <strong>{{ __('plusieurs tags') }}</strong> {{ __('simultanément.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('Chaque tag génère une page dédiée listant tous les articles associés.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('Vous pouvez attribuer une couleur à chaque tag pour une identification visuelle rapide.') }}</p>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment créer un tag ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Nouveau tag') }}</strong> {{ __('en haut à droite.') }}</li>
        <li class="mb-1">{{ __('Saisissez un nom court et significatif.') }}</li>
        <li class="mb-1">{{ __('Choisissez une couleur pour le différencier visuellement.') }}</li>
        <li>{{ __('Enregistrez. Le tag sera disponible immédiatement lors de la rédaction.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="zap" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Réutilisez les tags existants plutôt que d\'en créer des doublons (ex. "Laravel" et "laravel"). Des tags cohérents et bien réutilisés') }} <strong>{{ __('améliorent le maillage interne') }}</strong> {{ __('et le SEO de votre blog.') }}
    </p>
</div>
