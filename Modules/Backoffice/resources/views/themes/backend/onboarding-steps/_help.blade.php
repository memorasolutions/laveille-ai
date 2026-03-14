<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Les <strong>{{ __('étapes d\'accueil') }}</strong> (onboarding) constituent un guide pas à pas
        qui aide les <strong>{{ __('nouveaux utilisateurs') }}</strong> à découvrir votre application
        et à effectuer les actions essentielles dès leur première connexion.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="map" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Chaque étape représente une') }} <strong>{{ __('action que l\'utilisateur doit accomplir') }}</strong>
        {{ __('(compléter son profil, créer son premier article, etc.).') }}
        {{ __('L\'ordre d\'affichage, le contenu et les conditions de complétion sont entièrement configurables.') }}
        {{ __('Une étape désactivée est masquée sans être supprimée.') }}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Exemples d\'étapes') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1" style="min-width:36px;">1</span>
            <div>
                <strong class="small">{{ __('Compléter le profil') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('L\'utilisateur renseigne son nom, avatar et préférences.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1" style="min-width:36px;">2</span>
            <div>
                <strong class="small">{{ __('Explorer le tableau de bord') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Visite guidée des fonctionnalités principales.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1" style="min-width:36px;">3</span>
            <div>
                <strong class="small">{{ __('Créer un premier contenu') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Première action concrète pour engager l\'utilisateur.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="trending-up" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Un bon onboarding') }} <strong>{{ __('réduit significativement les demandes de support') }}</strong>
        {{ __('et améliore le taux d\'activation des nouveaux utilisateurs.') }}
        {{ __('Limitez le nombre d\'étapes à') }} <strong>3-5 maximum</strong>
        {{ __('pour ne pas décourager les nouveaux arrivants.') }}
    </p>
</div>
