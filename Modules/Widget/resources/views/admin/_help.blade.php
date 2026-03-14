<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Les <strong>Widgets</strong> sont des <strong>blocs de contenu réutilisables</strong> que vous pouvez placer
        dans différentes zones de votre site (sidebar, pied de page, bannière, etc.).
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layout-grid" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Exemples de widgets') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Bannière promotionnelle, encart newsletter, liens vers les réseaux sociaux, appel à l\'action (CTA), bloc de contact rapide, publicité... Tout contenu répétable peut devenir un widget.') }}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment les utiliser ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Étape 1') }}</span>
            <div>
                <strong class="small">{{ __('Créez le widget') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Donnez-lui un titre, un type de contenu et assignez-le à une zone d\'affichage.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Étape 2') }}</span>
            <div>
                <strong class="small">{{ __('Placez-le via shortcode') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Utilisez le shortcode généré pour intégrer le widget dans vos pages ou templates Blade.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Zones d\'affichage') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Les widgets sont regroupés par zone (sidebar, footer, header...).') }}</li>
        <li class="mb-1">{{ __('Glissez-déposez pour réordonner les widgets dans une même zone.') }}</li>
        <li class="mb-1">{{ __('Modifiez un widget pour changer son contenu ou le déplacer dans une autre zone.') }}</li>
        <li>{{ __('Supprimez un widget sans impact sur le contenu de votre site (si non utilisé).') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="toggle-left" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Un widget peut être activé ou désactivé sans le supprimer. Pratique pour masquer temporairement un contenu saisonnier (promotion, événement) sans perdre votre configuration.') }}
    </p>
</div>
