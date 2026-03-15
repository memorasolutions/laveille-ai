<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('Les <strong>journaux système</strong> sont les enregistrements techniques de tout ce qui se passe dans votre application : <strong>erreurs</strong>, <strong>avertissements</strong> et <strong>informations</strong> générés automatiquement par Laravel et vos modules.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layers" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Niveaux de gravité') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger" style="min-width:90px;">Emergency</span>
            <span class="text-muted small">{{ __('Système inutilisable - intervention immédiate requise') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger bg-opacity-75" style="min-width:90px;">Error</span>
            <span class="text-muted small">{{ __('Erreur applicative - quelque chose a échoué') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark" style="min-width:90px;">Warning</span>
            <span class="text-muted small">{{ __('Avertissement - situation anormale mais non bloquante') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" style="min-width:90px;">Info</span>
            <span class="text-muted small">{{ __('Information - événements normaux du cycle de vie') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary" style="min-width:90px;">Debug</span>
            <span class="text-muted small">{{ __('Débogage - détails techniques pour le développement') }}</span>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="search" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Quand consulter les journaux ?') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{!! __('Après une <strong>erreur 500</strong> signalée par un utilisateur') !!}</li>
        <li class="mb-1">{!! __('Quand un <strong>comportement inattendu</strong> se produit') !!}</li>
        <li class="mb-1">{!! __('Pour vérifier si un <strong>job planifié</strong> s\'est exécuté correctement') !!}</li>
        <li>{!! __('Pour diagnostiquer une <strong>lenteur ou un timeout</strong>') !!}</li>
    </ul>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="filter" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Utilisez les filtres par niveau pour trouver rapidement les erreurs critiques.') }}
        {!! __('Commencez toujours par <strong>Error</strong> et <strong>Warning</strong> avant d\'explorer les niveaux inférieurs.') !!}
        {{ __('Le bouton « Vider les journaux » efface définitivement tous les enregistrements.') }}
    </p>
</div>
