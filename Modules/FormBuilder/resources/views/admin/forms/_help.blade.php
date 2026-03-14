<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Le') }} <strong>{{ __('constructeur de formulaires') }}</strong> {{ __('vous permet de créer des formulaires personnalisés sans écrire une seule ligne de code. Collectez des données, des candidatures, des avis, etc.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layers" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Types de champs disponibles') }}
    </h6>
    <div class="d-flex flex-wrap gap-1">
        <span class="badge bg-light text-dark border">{{ __('Texte') }}</span>
        <span class="badge bg-light text-dark border">{{ __('Email') }}</span>
        <span class="badge bg-light text-dark border">{{ __('Téléphone') }}</span>
        <span class="badge bg-light text-dark border">{{ __('Sélection') }}</span>
        <span class="badge bg-light text-dark border">{{ __('Case à cocher') }}</span>
        <span class="badge bg-light text-dark border">{{ __('Fichier') }}</span>
        <span class="badge bg-light text-dark border">{{ __('Captcha') }}</span>
        <span class="badge bg-light text-dark border">{{ __('Zone de texte') }}</span>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Glisser-déposer') }}</strong> {{ __('des champs pour composer votre formulaire.') }}</li>
        <li class="mb-1"><strong>{{ __('Validations') }}</strong> {{ __('configurables par champ (obligatoire, format, longueur...).') }}</li>
        <li class="mb-1"><strong>{{ __('Emails de notification') }}</strong> {{ __('à chaque nouvelle soumission.') }}</li>
        <li><strong>{{ __('Shortcode') }}</strong> {{ __('à insérer dans n\'importe quelle page de votre site.') }}</li>
    </ul>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="code" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Intégration') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Chaque formulaire génère un') }} <strong>{{ __('shortcode') }}</strong>
        {{ __('(ex :') }} <code>[form:mon-formulaire]</code>{{ __(') à insérer dans vos pages statiques ou articles de blog. Le formulaire s\'affiche automatiquement à l\'emplacement du shortcode.') }}
    </p>
</div>
