<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        La <strong>{{ __('médiathèque') }}</strong> est la bibliothèque centralisée de tous vos fichiers :
        images, documents et vidéos. Tout fichier uploadé depuis n'importe quel module de l'application
        est stocké ici et peut être réutilisé.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="file-check" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Formats supportés') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" style="min-width:100px;">{{ __('Images') }}</span>
            <span class="text-muted small">JPG, PNG, GIF, SVG, WebP</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger" style="min-width:100px;">{{ __('Documents') }}</span>
            <span class="text-muted small">PDF</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success" style="min-width:100px;">{{ __('Vidéos') }}</span>
            <span class="text-muted small">MP4, MOV, AVI</span>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="zap" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Upload') }}</strong> {{ __('- glisser-déposer ou sélection de fichiers') }}</li>
        <li class="mb-1"><strong>{{ __('Recherche') }}</strong> {{ __('- retrouvez un fichier par son nom') }}</li>
        <li class="mb-1"><strong>{{ __('Aperçu') }}</strong> {{ __('- prévisualisation intégrée pour les images') }}</li>
        <li><strong>{{ __('Suppression') }}</strong> {{ __('- libère l\'espace disque immédiatement') }}</li>
    </ul>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Avant de supprimer un fichier, assurez-vous qu\'il n\'est') }} <strong>{{ __('pas utilisé') }}</strong>
        {{ __('dans un article, une page ou un composant.') }}
        {{ __('La suppression est') }} <strong>{{ __('définitive') }}</strong>
        {{ __('et libère immédiatement l\'espace disque correspondant.') }}
    </p>
</div>
