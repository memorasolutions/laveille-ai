<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        La <strong>FAQ</strong> (Foire Aux Questions) regroupe les <strong>questions fréquentes affichées sur votre site</strong>.
        C'est un outil essentiel pour réduire les demandes de support et guider vos visiteurs.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="list-ordered" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Créez, ordonnez et catégorisez vos questions et réponses. Glissez-déposez les entrées pour définir l\'ordre d\'affichage sur votre site public.') }}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="search" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('SEO automatique') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Auto') }}</span>
            <div>
                <strong class="small">{{ __('Schema.org JSON-LD') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Chaque FAQ génère automatiquement du balisage Schema.org JSON-LD, ce qui améliore votre référencement et peut afficher les questions dans les résultats Google.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Statut') }}</span>
            <div>
                <strong class="small">{{ __('Publié / Brouillon') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Une question en brouillon n\'apparaît pas sur le site public. Pratique pour préparer du contenu à l\'avance.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Ajouter une question') }}</strong> {{ __('pour créer une nouvelle entrée.') }}</li>
        <li class="mb-1">{{ __('Remplissez la question, la réponse et optionnellement une catégorie.') }}</li>
        <li class="mb-1">{{ __('Publiez la question pour la rendre visible sur le site public.') }}</li>
        <li>{{ __('Glissez-déposez les entrées pour réorganiser l\'ordre d\'affichage.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="star" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Mettez les questions les plus demandées en premier. Les visiteurs trouvent plus rapidement ce qu\'ils cherchent et votre taux de rebond diminue.') }}
    </p>
</div>
