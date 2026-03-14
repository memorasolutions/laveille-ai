<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Créez et envoyez des') }} <strong>{{ __('campagnes email') }}</strong>
        {{ __('à vos abonnés. Composez votre message, ciblez un segment et analysez les résultats.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="bar-chart-2" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Métriques clés') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Ouverture') }}</span>
            <p class="text-muted small mb-0">{{ __('Pourcentage d\'abonnés ayant ouvert l\'email.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Clic') }}</span>
            <p class="text-muted small mb-0">{{ __('Pourcentage d\'abonnés ayant cliqué sur un lien.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-secondary mt-1">{{ __('Désabonnement') }}</span>
            <p class="text-muted small mb-0">{{ __('Nombre d\'abonnés ayant quitté la liste suite à cet envoi.') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Créez une nouvelle campagne et choisissez un template.') }}</li>
        <li class="mb-1">{{ __('Rédigez votre objet et votre contenu.') }}</li>
        <li class="mb-1">{{ __('Sélectionnez un segment d\'abonnés ou envoyez à tous.') }}</li>
        <li class="mb-1"><strong>{{ __('Envoyez un test') }}</strong> {{ __('sur votre propre adresse avant la diffusion.') }}</li>
        <li>{{ __('Planifiez ou envoyez immédiatement.') }}</li>
    </ol>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="zap" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Testez') }} <strong>{{ __('toujours') }}</strong> {{ __('votre campagne avec un envoi de test avant la diffusion finale. Vérifiez l\'affichage sur mobile et desktop, et validez tous vos liens.') }}
    </p>
</div>
