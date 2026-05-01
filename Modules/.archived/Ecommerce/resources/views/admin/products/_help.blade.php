<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('Gérez vos <strong>produits</strong>, leurs <strong>variantes</strong> (SKU, prix, stock) et assignez-les à des <strong>catégories</strong>.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="package" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Structure produit') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('Chaque produit peut avoir <strong>plusieurs variantes</strong> (taille, couleur) avec leur propre SKU, prix et stock.<br>Organisez-les par <strong>catégories hiérarchiques</strong>.') !!}
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
                <strong class="small">{{ __('Créer le produit') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Nom, description, images et prix de base.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">2</span>
            <div>
                <strong class="small">{{ __('Ajouter les variantes') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('SKU unique, prix spécifique et quantité en stock.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">3</span>
            <div>
                <strong class="small">{{ __('Assigner les catégories') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Un produit peut appartenir à plusieurs catégories.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="search" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('SEO') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Les slugs sont générés automatiquement à partir du nom.') }}</li>
        <li class="mb-1">{{ __('Ajoutez des descriptions riches pour le référencement.') }}</li>
        <li>{{ __('Renseignez les balises alt sur les images.') }}</li>
    </ul>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(239,68,68,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-danger" style="width:16px;height:16px;"></i>
        {{ __('Attention') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Supprimer un produit supprime définitivement toutes ses variantes.') }}</li>
    </ul>
</div>
