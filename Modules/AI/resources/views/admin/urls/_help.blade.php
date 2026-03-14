<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">{{ __('Configurez et gérez les sources web à scraper pour enrichir automatiquement la base de connaissances de votre IA.') }}</p>
</div>
{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Scraping de sites multi-pages.') }}</li>
        <li>{{ __('Respect strict des directives du fichier robots.txt.') }}</li>
        <li>{{ __('Planification de la fréquence de mise à jour.') }}</li>
        <li>{{ __('Extraction automatique du contenu principal.') }}</li>
    </ul>
</div>
{{-- Section 3 - spécifique --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="globe" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Processus de scraping') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Vérification robots.txt') }} &rarr; {{ __('Extraction contenu') }} &rarr; {{ __('Découpage chunks') }} &rarr; {{ __('Indexation') }}</li>
    </ul>
</div>
{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Assurez-vous d\'avoir les droits nécessaires sur les URLs que vous souhaitez scraper.') }}</li>
        <li>{{ __('Les pages trop volumineuses ou inaccessibles seront automatiquement ignorées.') }}</li>
    </ul>
</div>
