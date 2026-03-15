<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Organisez vos produits avec une <strong>structure hiérarchique</strong> parent/enfant flexible.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="folder-tree" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Hiérarchie flexible') }}
    </h6>
    <p class="text-muted small mb-0">
        Créez des <strong>catégories imbriquées</strong> à plusieurs niveaux.<br>
        Définissez la <strong>position</strong> pour contrôler l'ordre d'affichage.
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">1</span>
            <div>
                <strong class="small">{{ __('Créer la catégorie') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Nom, slug et parent optionnel.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">2</span>
            <div>
                <strong class="small">{{ __('Définir la position') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Contrôle l\'ordre d\'affichage dans la boutique.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">3</span>
            <div>
                <strong class="small">{{ __('Activer ou désactiver') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Contrôle la visibilité dans la boutique.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layers" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Organisation') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Limitez-vous à 3-4 niveaux de profondeur pour la clarté.') }}</li>
        <li class="mb-1">{{ __('Utilisez des noms courts et descriptifs.') }}</li>
        <li>{{ __('Un produit peut appartenir à plusieurs catégories.') }}</li>
    </ul>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(239,68,68,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-danger" style="width:16px;height:16px;"></i>
        {{ __('Attention') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Désactiver une catégorie masque tous les produits qui y sont exclusivement rattachés.') }}</li>
    </ul>
</div>
