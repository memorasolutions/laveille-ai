<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Les') }} <strong>{{ __('soumissions') }}</strong> {{ __('sont les réponses reçues via vos formulaires personnalisés. Chaque envoi est horodaté et conservé pour consultation.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="activity" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Statuts') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Nouveau') }}</span>
            <p class="text-muted small mb-0">{{ __('Soumission reçue, pas encore consultée.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-secondary mt-1">{{ __('Lu') }}</span>
            <p class="text-muted small mb-0">{{ __('Soumission déjà consultée dans le détail.') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Consulter') }}</strong> {{ __('le détail de chaque soumission avec toutes les réponses.') }}</li>
        <li class="mb-1"><strong>{{ __('Filtrer') }}</strong> {{ __('par statut (nouveau/lu) ou par recherche.') }}</li>
        <li class="mb-1"><strong>{{ __('Exporter') }}</strong> {{ __('en CSV pour traitement dans un tableur.') }}</li>
        <li><strong>{{ __('Supprimer') }}</strong> {{ __('les soumissions obsolètes.') }}</li>
    </ul>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('RGPD') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Les soumissions contiennent des') }} <strong>{{ __('données personnelles') }}</strong>
        {{ __('(adresse IP, informations saisies). Pensez à') }} <strong>{{ __('purger régulièrement') }}</strong>
        {{ __('les anciennes données conformément à votre politique de conservation. Vous êtes responsable de la durée de conservation de ces données.') }}
    </p>
</div>
