<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        La <strong>recherche globale</strong> interroge simultanément toutes les ressources
        de votre administration - utilisateurs, articles, paramètres, pages, plans et catégories -
        en une seule requête.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="database" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <p class="text-muted small mb-0">
        Tapez un terme dans le champ de recherche. L'application interroge
        <strong>simultanément</strong> tous les types de contenus et affiche les résultats
        regroupés par catégorie. Utilisez le filtre <strong>Type</strong> pour cibler
        une ressource spécifique.
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Ressources indexées') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Utilisateurs') }}</span>
            <div>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Recherche par nom et adresse courriel.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Contenu') }}</span>
            <div>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Articles, pages statiques et catégories de blog.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-secondary mt-1">{{ __('Système') }}</span>
            <div>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Paramètres (clé/valeur), rôles et plans tarifaires.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Conseils d\'utilisation') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Utilisez des termes') }} <strong>{{ __('partiels') }}</strong> {{ __('pour élargir les résultats (ex. : « mar » trouve « marketing » et « marque »).') }}</li>
        <li class="mb-1">{{ __('Filtrez par') }} <strong>{{ __('type') }}</strong> {{ __('si vous cherchez une ressource spécifique.') }}</li>
        <li>{{ __('Cliquez sur') }} <strong>{{ __('Modifier') }}</strong> {{ __('directement depuis les résultats pour accéder à la fiche.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="zap" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Recherche rapide') }}</strong> {{ __('- la barre de recherche globale dans le header effectue la même recherche sans quitter la page en cours.') }}</li>
        <li><strong>{{ __('Réindexation') }}</strong> {{ __('- après des modifications massives de contenu, lancez') }} <code>php artisan scout:import</code> {{ __('pour mettre à jour l\'index.') }}</li>
    </ul>
</div>
