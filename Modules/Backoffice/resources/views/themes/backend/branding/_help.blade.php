<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('La page <strong>Identité visuelle</strong> vous permet de <strong>personnaliser l\'apparence</strong> de votre administration : couleurs, polices, logos et pied de page, pour une expérience à votre image.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="paintbrush" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-primary mt-1">{{ __('Identité') }}</span>
            <p class="text-muted small mb-0">{{ __('Nom du site, description, titre de la page de connexion') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-warning mt-1">{{ __('Couleurs') }}</span>
            <p class="text-muted small mb-0">{{ __('Palette complète : primaire, secondaire, sidebar, en-tête') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-info mt-1">{{ __('Typographie') }}</span>
            <p class="text-muted small mb-0">{{ __('Police du corps, taille, graisse, espacement') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-success mt-1">{{ __('Logos') }}</span>
            <p class="text-muted small mb-0">{{ __('Logo clair/sombre, icône, favicon') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="eye" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('Les changements se reflètent <strong>immédiatement dans le backoffice</strong> après sauvegarde. La colonne de droite affiche un <strong>aperçu en temps réel</strong> pendant que vous modifiez.') !!}
    </p>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="star" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('Utilisez l\'<strong>aperçu en direct</strong> (colonne de droite) pour voir vos changements avant de sauvegarder. Le bouton <em>Réinitialiser</em> sur l\'onglet Couleurs restaure la palette par défaut en un clic.') !!}
    </p>
</div>
