<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Un') }} <strong>{{ __('tenant') }}</strong> {{ __('est un') }} <strong>{{ __('locataire') }}</strong>
        {{ __('de votre application. Dans une architecture multi-tenant, chaque tenant dispose de ses propres données, isolées de celles des autres.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Créer') }}</strong> {{ __(':') }} {{ __('provisionnez un nouveau tenant avec son slug et son propriétaire.') }}</li>
        <li class="mb-1"><strong>{{ __('Configurer') }}</strong> {{ __(':') }} {{ __('associez un domaine personnalisé et des paramètres spécifiques.') }}</li>
        <li class="mb-1"><strong>{{ __('Suspendre') }}</strong> {{ __(':') }} {{ __('bloquez l\'accès sans supprimer les données.') }}</li>
        <li><strong>{{ __('Supprimer') }}</strong> {{ __(':') }} {{ __('supprimez définitivement le tenant et toutes ses données.') }}</li>
    </ul>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="database" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Actif') }}</span>
            <div>
                <strong class="small">{{ __('Tenant actif') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Le tenant et ses utilisateurs ont accès normal à l\'application.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-secondary mt-1">{{ __('Inactif') }}</span>
            <div>
                <strong class="small">{{ __('Tenant suspendu') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('L\'accès est bloqué mais les données sont préservées en base.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('La') }} <strong>{{ __('suspension') }}</strong> {{ __('est réversible - préférez-la à la suppression en cas de doute.') }}</li>
        <li class="mb-1">{{ __('Chaque tenant peut avoir son propre') }} <strong>{{ __('domaine personnalisé') }}</strong> {{ __('(ex: client.exemple.com).') }}</li>
        <li>{{ __('La suppression est') }} <strong>{{ __('définitive') }}</strong> {{ __('et supprime toutes les données associées au tenant.') }}</li>
    </ul>
</div>
