<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Les <strong>Shortcodes</strong> sont des codes courts réutilisables que vous insérez dans vos contenus.
        Ils sont automatiquement <strong>remplacés par du contenu dynamique</strong> à l'affichage.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="code-2" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Exemple concret') }}
    </h6>
    <p class="text-muted small mb-2">{{ __('Vous écrivez dans votre contenu :') }}</p>
    <code class="d-block bg-white border rounded px-3 py-2 small font-monospace mb-2">[contact-form]</code>
    <p class="text-muted small mb-0">{{ __('Et votre visiteur voit automatiquement un formulaire de contact complet.') }}</p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment les utiliser ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Créez ou copiez le shortcode depuis cette liste.') }}</li>
        <li class="mb-1">{{ __('Collez-le dans') }} <strong>{{ __('n\'importe quel contenu') }}</strong> {{ __('(article, page, widget...).') }}</li>
        <li>{{ __('Le contenu est généré') }} <strong>{{ __('automatiquement') }}</strong> {{ __('à chaque affichage.') }}</li>
    </ol>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        Les shortcodes sont <strong>{{ __('interprétés à l\'affichage') }}</strong>, pas à l'édition.
        Vous pouvez donc modifier le contenu d'un shortcode et toutes les pages l'utilisant seront mises à jour instantanément.
    </p>
</div>
