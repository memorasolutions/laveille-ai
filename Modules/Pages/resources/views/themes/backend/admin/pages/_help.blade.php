<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Les') }} <strong>{{ __('pages statiques') }}</strong> {{ __('sont les pages fixes de votre site : À propos, Mentions légales, CGU, Politique de confidentialité, etc. Elles ont une URL permanente et un contenu stable.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="edit-3" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Éditeur de contenu') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Chaque page dispose de l\'éditeur visuel') }} <strong>{{ __('TipTap') }}</strong>
        {{ __('qui vous permet d\'ajouter du texte enrichi, des titres, des listes, des liens, des images et des blocs de code sans toucher au HTML.') }}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Créer') }}</strong> {{ __('une page avec titre, contenu et slug URL personnalisé.') }}</li>
        <li class="mb-1"><strong>{{ __('Modifier') }}</strong> {{ __('le contenu à tout moment sans redéploiement.') }}</li>
        <li class="mb-1"><strong>{{ __('Publier / Dépublier') }}</strong> {{ __('selon vos besoins.') }}</li>
        <li><strong>{{ __('Ordonner') }}</strong> {{ __('les pages et définir leur visibilité dans la navigation.') }}</li>
    </ul>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Créez au minimum les') }} <strong>{{ __('pages légales obligatoires') }}</strong>
        {{ __('pour votre site : Conditions générales d\'utilisation, Politique de confidentialité et Mentions légales. Ces pages sont requises par le RGPD et la loi française.') }}
    </p>
</div>
