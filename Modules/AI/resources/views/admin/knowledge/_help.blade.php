<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">{{ __('Gérez la base de connaissances de l\'IA, incluant les documents, les segments de texte (chunks) et les embeddings pour garantir une recherche sémantique performante.') }}</p>
</div>
{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Synchronisation des contenus existants.') }}</li>
        <li>{{ __('Génération et gestion des embeddings vectoriels.') }}</li>
        <li>{{ __('Découpage intelligent des contenus en segments (chunks).') }}</li>
        <li>{{ __('Moteur de recherche sémantique avancé.') }}</li>
    </ul>
</div>
{{-- Section 3 - spécifique --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="database" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Types de sources') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Saisie manuelle de documents.') }}</li>
        <li>{{ __('Import depuis les FAQ, Articles et Pages.') }}</li>
        <li>{{ __('Intégration de Services et URLs externes.') }}</li>
    </ul>
</div>
{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('La qualité des réponses de l\'IA dépend directement de la précision de ces documents.') }}</li>
        <li>{{ __('Les embeddings sont mis à jour automatiquement lors de la synchronisation des sources.') }}</li>
    </ul>
</div>
