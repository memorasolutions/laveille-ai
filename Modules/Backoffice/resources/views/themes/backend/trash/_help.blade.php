<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        La <strong>Corbeille</strong> est un espace de sécurité où vont les éléments supprimés
        avant leur <strong>suppression définitive</strong>. Vous pouvez les récupérer tant qu'ils n'ont pas été purgés.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="trash-2" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Un élément supprimé depuis le backoffice') }} <strong>{{ __('n\'est pas immédiatement perdu') }}</strong>.</li>
        <li class="mb-1">{{ __('Il est déplacé dans la corbeille (soft delete).') }}</li>
        <li class="mb-1">{{ __('Vous pouvez le') }} <strong>{{ __('restaurer') }}</strong> {{ __('ou le') }} <strong>{{ __('supprimer définitivement') }}</strong>.</li>
        <li>{{ __('La corbeille se vide automatiquement après') }} <strong>30 {{ __('jours') }}</strong>.</li>
    </ol>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Actions possibles') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Restaurer') }}</span>
            <p class="text-muted small mb-0">{{ __('Remet l\'élément à sa place d\'origine avec toutes ses données.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Supprimer') }}</span>
            <p class="text-muted small mb-0">{{ __('Suppression définitive et irréversible de la base de données.') }}</p>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        La corbeille protège contre les <strong>{{ __('suppressions accidentelles') }}</strong>.
        Avant de vider la corbeille, vérifiez que vous n'avez pas besoin de récupérer certains éléments.
        La suppression définitive est <strong>{{ __('irréversible') }}</strong>.
    </p>
</div>
