<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Le') }} <strong>{{ __('tableau de bord') }}</strong> {{ __('est votre page d\'accueil de l\'administration. Il vous offre une') }} <strong>{{ __('vue d\'ensemble instantanée') }}</strong> {{ __('de votre application : statistiques clés, activité récente et accès rapides aux fonctions importantes.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layout-grid" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Que voit-on ici ?') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Statistiques clés') }}</strong> {{ __('– nombre d\'utilisateurs, articles, pages et modules actifs') }}</li>
        <li class="mb-1"><strong>{{ __('Graphique inscriptions') }}</strong> {{ __('– évolution des inscriptions sur les 12 derniers mois') }}</li>
        <li class="mb-1"><strong>{{ __('Activité récente') }}</strong> {{ __('– les 8 dernières actions effectuées dans l\'administration') }}</li>
        <li><strong>{{ __('Actions rapides') }}</strong> {{ __('– raccourcis vers les opérations les plus fréquentes') }}</li>
    </ul>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="lock" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Personnalisation selon vos permissions') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Super admin') }}</span>
            <div>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Accès à toutes les statistiques, tous les modules et toutes les actions rapides.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Admin / éditeur') }}</span>
            <div>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Les widgets et raccourcis s\'adaptent automatiquement à vos permissions. Certaines actions peuvent ne pas être visibles.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="server" class="text-secondary" style="width:16px;height:16px;"></i>
        {{ __('Informations système') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('En bas de page, un tableau affiche les informations techniques de votre serveur : version Laravel, version PHP, environnement d\'exécution (production/local) et nombre de modules actifs. Utile pour le support technique.') }}
    </p>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="star" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Consultez le tableau de bord') }} <strong>{{ __('chaque matin') }}</strong> {{ __('pour détecter rapidement toute anomalie ou pic d\'activité inhabituel.') }}</li>
        <li class="mb-1">{{ __('Le graphique des inscriptions vous aide à') }} <strong>{{ __('mesurer l\'impact de vos campagnes') }}</strong> {{ __('marketing.') }}</li>
        <li>{{ __('Les statistiques se mettent à jour') }} <strong>{{ __('en temps réel') }}</strong> {{ __('à chaque chargement de page.') }}</li>
    </ul>
</div>
