<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Le module') }} <strong>{{ __('Réservations') }}</strong> {{ __('permet de gérer les rendez-vous, les services offerts, les créneaux disponibles et les communications avec vos clients.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layout-grid" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités principales') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Rendez-vous') }}</strong> – {{ __('consulter, confirmer, annuler ou assigner un thérapeute') }}</li>
        <li class="mb-1"><strong>{{ __('Services') }}</strong> – {{ __('définir les services avec durée, prix et questions d\'accueil') }}</li>
        <li class="mb-1"><strong>{{ __('Disponibilités') }}</strong> – {{ __('heures de travail, jours bloqués et créneaux spéciaux') }}</li>
        <li class="mb-1"><strong>{{ __('Coupons et forfaits') }}</strong> – {{ __('réductions, cartes-cadeaux et forfaits multi-séances') }}</li>
        <li class="mb-1"><strong>{{ __('Statistiques') }}</strong> – {{ __('taux d\'annulation, revenus et services populaires') }}</li>
        <li class="mb-1"><strong>{{ __('Webhooks') }}</strong> – {{ __('notifications automatiques vers des services externes') }}</li>
    </ul>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="zap" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Astuces') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Les clients reçoivent des rappels automatiques par courriel et SMS avant leur rendez-vous.') }}</li>
        <li class="mb-1">{{ __('Un widget intégrable permet d\'ajouter la prise de rendez-vous sur n\'importe quel site externe.') }}</li>
        <li class="mb-1">{{ __('Les no-shows sont automatiquement détectés et comptabilisés sur la fiche client.') }}</li>
    </ul>
</div>
