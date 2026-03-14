<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('L\'') }}<strong>{{ __('historique des révisions') }}</strong> {{ __('conserve une trace de toutes les modifications apportées à cet article. Vous pouvez consulter les anciennes versions, comparer les changements et restaurer une version précédente en cas de besoin.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="history" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités disponibles') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-secondary mt-1">{{ __('Voir') }}</span>
            <p class="text-muted small mb-0">{{ __('Consultez le contenu complet d\'une version antérieure de l\'article.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Comparer') }}</span>
            <p class="text-muted small mb-0">{{ __('Affichez les différences ligne par ligne entre deux versions (ajouts en vert, suppressions en rouge).') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('Restaurer') }}</span>
            <p class="text-muted small mb-0">{{ __('Remplacez le contenu actuel par une version précédente. L\'opération crée elle-même une nouvelle révision.') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('Chaque') }} <strong>{{ __('sauvegarde de l\'article') }}</strong> {{ __('crée automatiquement une révision numérotée.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('Chaque révision enregistre la date, l\'heure et l\'auteur de la modification.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0 mt-1" style="width:14px;height:14px;"></i>
            <p class="text-muted small mb-0">{{ __('La restauration est') }} <strong>{{ __('non destructive') }}</strong> {{ __(': votre version actuelle devient elle-même une révision.') }}</p>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment utiliser les révisions ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Identifiez la révision souhaitée dans la liste (date et auteur visibles).') }}</li>
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Voir') }}</strong> {{ __('pour consulter le contenu de cette version.') }}</li>
        <li class="mb-1">{{ __('Utilisez') }} <strong>{{ __('Comparer') }}</strong> {{ __('pour visualiser exactement ce qui a changé.') }}</li>
        <li>{{ __('Cliquez sur') }} <strong>{{ __('Restaurer') }}</strong> {{ __('si vous souhaitez revenir à cette version.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Les révisions permettent de') }} <strong>{{ __('travailler sans crainte') }}</strong> {{ __(': vous pouvez modifier, expérimenter et restructurer votre article en sachant que toutes les versions précédentes restent accessibles et récupérables à tout moment.') }}
    </p>
</div>
