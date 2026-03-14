<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Un <strong>Feature Flag</strong> (ou « drapeau de fonctionnalité ») est un interrupteur ON/OFF
        qui vous permet d'<strong>activer ou désactiver une fonctionnalité</strong> de votre application
        sans toucher au code et sans redéployer.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="home" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Pensez-y comme un interrupteur de lumière') }}
    </h6>
    <p class="text-muted small mb-0">
        Imaginez votre maison : chaque pièce a un interrupteur. Vous pouvez allumer le salon
        sans toucher à la cuisine. Les Feature Flags fonctionnent pareil : chaque fonctionnalité
        a son propre interrupteur.
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Exemples concrets') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Activé') }}</span>
            <div>
                <strong class="small">{{ __('Mode blog activé') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Les visiteurs voient la section blog sur votre site.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Désactivé') }}</span>
            <div>
                <strong class="small">{{ __('Mode blog désactivé') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __("La section blog disparaît du site. Le contenu reste en base de données, rien n'est perdu.") }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Activé') }}</span>
            <div>
                <strong class="small">{{ __('Inscription ouverte') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Les nouveaux utilisateurs peuvent créer un compte.') }}</p>
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
        <li class="mb-1">{{ __('Trouvez le flag que vous voulez modifier dans la liste ci-dessous.') }}</li>
        <li class="mb-1">{{ __('Cliquez sur le') }} <strong>{{ __('bouton toggle') }}</strong> {{ __('(interrupteur) pour l\'activer ou le désactiver.') }}</li>
        <li class="mb-1">{{ __('Le changement prend effet') }} <strong>{{ __('immédiatement') }}</strong>, {{ __('sans besoin de redémarrer quoi que ce soit.') }}</li>
        <li>{{ __('Pour revenir en arrière, cliquez simplement à nouveau sur le toggle.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Pourquoi c\'est utile ?') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Tester') }}</strong> {{ __('une nouvelle fonctionnalité sans risque.') }}</li>
        <li class="mb-1"><strong>{{ __('Désactiver') }}</strong> {{ __('rapidement quelque chose qui pose problème.') }}</li>
        <li class="mb-1"><strong>{{ __('Lancer') }}</strong> {{ __('progressivement une fonctionnalité.') }}</li>
        <li><strong>{{ __('Personnaliser') }}</strong> {{ __("l'expérience selon vos besoins du moment.") }}</li>
    </ul>
</div>
