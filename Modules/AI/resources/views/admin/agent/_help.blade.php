<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">{{ __('Interface dédiée aux agents humains pour gérer, reprendre et clôturer les conversations escaladées par l\'IA.') }}</p>
</div>
{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Prise en charge (claim) des conversations en attente.') }}</li>
        <li>{{ __('Réponse directe aux utilisateurs depuis le backoffice.') }}</li>
        <li>{{ __('Clôture des tickets une fois résolus.') }}</li>
        <li>{{ __('Consultation de l\'historique des échanges avec l\'IA.') }}</li>
    </ul>
</div>
{{-- Section 3 - spécifique --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="headphones" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Flux de travail') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Conversation IA') }} &rarr; {{ __('Demande escalade') }} &rarr; {{ __('Agent réclame') }} &rarr; {{ __('Répond') }} &rarr; {{ __('Ferme') }}</li>
    </ul>
</div>
{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('L\'utilisateur est automatiquement notifié lorsqu\'un agent rejoint la conversation.') }}</li>
        <li>{{ __('L\'IA cesse d\'intervenir de manière autonome une fois la conversation réclamée par un humain.') }}</li>
    </ul>
</div>
