<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('Créez des <strong>codes promo</strong> : pourcentage, montant fixe ou livraison gratuite.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="tag" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Types de coupons') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('<strong>Pourcentage</strong> - réduction en % du panier.<br><strong>Montant fixe</strong> - réduction d\'un montant précis.<br><strong>Livraison gratuite</strong> - frais de port offerts.') !!}
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
                <strong class="small">{{ __('Créer le code') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Code unique et mémorable (ex: PROMO20).') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">2</span>
            <div>
                <strong class="small">{{ __('Choisir type et valeur') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Pourcentage, montant fixe ou livraison gratuite.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">3</span>
            <div>
                <strong class="small">{{ __('Définir les limites') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Date d\'expiration et nombre d\'utilisations maximum.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="percent" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bonnes pratiques') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Codes courts et faciles à retenir pour les campagnes marketing.') }}</li>
        <li class="mb-1">{{ __('Limitez le nombre d\'utilisations pour les offres exclusives.') }}</li>
        <li>{{ __('Testez le coupon avant de le diffuser.') }}</li>
    </ul>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(239,68,68,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-danger" style="width:16px;height:16px;"></i>
        {{ __('Attention') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Les coupons expirés sont automatiquement rejetés lors du paiement.') }}</li>
    </ul>
</div>
