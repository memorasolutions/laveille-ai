<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Les <strong>{{ __('plans d\'abonnement') }}</strong> définissent les offres commerciales de votre
        application SaaS : chaque plan détermine le <strong>{{ __('prix') }}</strong>,
        la <strong>{{ __('période') }}</strong> et les <strong>{{ __('fonctionnalités') }}</strong>
        auxquelles un abonné a accès.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="package" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Éléments d\'un plan') }}
    </h6>
    <div class="d-flex flex-column gap-1">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="check" style="width:14px;height:14px;" class="text-success flex-shrink-0"></i>
            <span class="text-muted small"><strong>{{ __('Nom et description') }}</strong> {{ __('- affiché sur la page de tarification') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="check" style="width:14px;height:14px;" class="text-success flex-shrink-0"></i>
            <span class="text-muted small"><strong>{{ __('Prix et période') }}</strong> {{ __('- mensuel ou annuel') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="check" style="width:14px;height:14px;" class="text-success flex-shrink-0"></i>
            <span class="text-muted small"><strong>{{ __('Fonctionnalités') }}</strong> {{ __('- liste des avantages inclus') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="check" style="width:14px;height:14px;" class="text-success flex-shrink-0"></i>
            <span class="text-muted small"><strong>{{ __('Limites') }}</strong> {{ __('- quotas (utilisateurs, projets, stockage...)') }}</span>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="credit-card" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Intégration Stripe') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Créez votre plan ici avec le prix et la période souhaités.') }}</li>
        <li class="mb-1">{{ __('Le plan est automatiquement') }} <strong>{{ __('synchronisé avec Stripe') }}</strong> {{ __('lors de la création.') }}</li>
        <li class="mb-1">{{ __('Les utilisateurs peuvent s\'abonner via le') }} <strong>{{ __('checkout Stripe sécurisé') }}</strong>.</li>
        <li>{{ __('Les paiements et renouvellements sont gérés') }} <strong>{{ __('automatiquement') }}</strong> {{ __('par Stripe.') }}</li>
    </ol>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Modifier un plan existant') }} <strong>{{ __('n\'affecte pas les abonnés actuels') }}</strong>
        {{ __('- ils conservent les conditions de leur souscription d\'origine.') }}
        {{ __('Pour changer les conditions de tous les abonnés, il est préférable de créer un') }}
        <strong>{{ __('nouveau plan') }}</strong>
        {{ __('et de migrer progressivement.') }}
    </p>
</div>
