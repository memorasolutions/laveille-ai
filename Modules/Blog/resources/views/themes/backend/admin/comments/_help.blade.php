<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('La') }} <strong>{{ __('modération des commentaires') }}</strong> {{ __('vous permet de gérer toutes les réactions laissées par vos lecteurs sur vos articles : approuver, rejeter, signaler comme spam ou répondre directement.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield-check" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités de modération') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Approuver') }}</span>
            <p class="text-muted small mb-0">{{ __('Le commentaire devient visible sur votre site pour tous les lecteurs.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Rejeter') }}</span>
            <p class="text-muted small mb-0">{{ __('Le commentaire est masqué sans être supprimé définitivement.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('Spam') }}</span>
            <p class="text-muted small mb-0">{{ __('Le commentaire est marqué comme indésirable pour améliorer la détection automatique.') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Modération automatique') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('Les commentaires peuvent être') }} <strong>{{ __('approuvés automatiquement') }}</strong> {{ __('si l\'auteur a déjà un commentaire approuvé.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('Vous pouvez configurer la modération dans les paramètres du blog (approbation manuelle ou automatique).') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('Les commentaires en attente sont signalés par un badge dans le menu.') }}</p>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment modérer ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Consultez la liste des commentaires en attente (filtrés par défaut).') }}</li>
        <li class="mb-1">{{ __('Lisez le commentaire et décidez de l\'action appropriée.') }}</li>
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Approuver') }}</strong>, <strong>{{ __('Rejeter') }}</strong> {{ __('ou') }} <strong>{{ __('Spam') }}</strong> {{ __('selon votre décision.') }}</li>
        <li>{{ __('Répondez directement si vous souhaitez engager la conversation.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="heart" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Répondre aux commentaires de vos lecteurs') }} <strong>{{ __('encourage l\'engagement') }}</strong> {{ __('et fidélise votre audience. Les articles avec des discussions actives ont tendance à mieux se positionner dans les moteurs de recherche.') }}
    </p>
</div>
