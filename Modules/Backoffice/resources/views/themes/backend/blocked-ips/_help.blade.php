<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Le blocage d\'IP est un') }} <strong>{{ __('bouclier de protection') }}</strong> {{ __('qui empêche une adresse réseau précise d\'accéder à votre site. C\'est votre premier rempart contre les attaques automatisées et les comportements malveillants.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="help-circle" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Quand bloquer une IP ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Bloquer') }}</span>
            <div>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Tentatives de connexion répétées (brute force), envoi de spam, scraping agressif ou comportement suspect identifié dans les logs.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Ne pas bloquer') }}</span>
            <div>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Votre propre IP, les IPs de votre équipe, les bots de moteurs de recherche légitimes (Googlebot, Bingbot).') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Entrez l\'adresse IP à bloquer dans le formulaire en haut.') }}</li>
        <li class="mb-1">{{ __('Ajoutez une') }} <strong>{{ __('raison') }}</strong> {{ __('pour vous souvenir pourquoi vous l\'avez bloquée.') }}</li>
        <li class="mb-1">{{ __('Le blocage est') }} <strong>{{ __('immédiat') }}</strong> {{ __('– cette IP ne peut plus accéder au site du tout.') }}</li>
        <li>{{ __('Pour lever le blocage, cliquez sur le menu Actions (⋯) puis "Débloquer".') }}</li>
    </ol>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="tag" class="text-secondary" style="width:16px;height:16px;"></i>
        {{ __('Types de blocage') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Manuel') }}</strong> {{ __('– ajouté par un administrateur depuis cette interface.') }}</li>
        <li class="mb-1"><strong>{{ __('Auto') }}</strong> {{ __('– déclenché automatiquement par le système (ex. : trop de tentatives de connexion échouées).') }}</li>
        <li>{{ __('Les deux types peuvent être levés manuellement à tout moment.') }}</li>
    </ul>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(220,53,69,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-danger" style="width:16px;height:16px;"></i>
        {{ __('Attention') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Ne bloquez jamais votre propre IP') }}</strong> {{ __('– vous seriez vous-même exclu du site.') }}</li>
        <li class="mb-1">{{ __('Vérifiez l\'IP dans les logs avant de bloquer : une même IP peut être partagée par plusieurs utilisateurs (réseau d\'entreprise, université).') }}</li>
        <li>{{ __('En cas de blocage accidentel, accédez au site depuis une autre IP ou directement en base de données.') }}</li>
    </ul>
</div>
