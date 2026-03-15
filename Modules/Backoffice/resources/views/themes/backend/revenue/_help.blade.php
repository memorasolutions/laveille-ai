<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('Le tableau de bord <strong>Revenus</strong> centralise vos <strong>métriques financières d\'abonnements</strong> en temps réel. Les données proviennent directement de <strong>Stripe</strong> - aucune saisie manuelle.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="bar-chart-2" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Métriques clés') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('<strong>MRR</strong> (Monthly Recurring Revenue) : revenu mensuel récurrent - la somme de tous les abonnements actifs ramenée sur un mois.<br><strong>ARR</strong> (Annual Recurring Revenue) : MRR × 12.<br><strong>Churn</strong> : pourcentage d\'abonnés perdus ce mois par rapport au mois précédent.') !!}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Statuts d\'abonnement') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Actif') }}</span>
            <div>
                <strong class="small">{{ __('Abonnement payant') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('L\'abonné paie et son accès est pleinement actif.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('En essai') }}</span>
            <div>
                <strong class="small">{{ __('Période d\'essai') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Accès gratuit temporaire avant le premier paiement.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning mt-1">{{ __('Grâce') }}</span>
            <div>
                <strong class="small">{{ __('Période de grâce') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Paiement échoué, accès maintenu temporairement en attendant une nouvelle tentative.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="pie-chart" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Graphiques') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Répartition des abonnements') }}</strong> {{ __(': visualisez la proportion actifs / essai / grâce / annulés.') }}</li>
        <li><strong>{{ __('Revenus par plan') }}</strong> {{ __(': comparez la contribution financière de chaque plan tarifaire.') }}</li>
    </ul>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Temps réel') }}</strong> {{ __('- les chiffres reflètent l\'état actuel de Stripe à chaque chargement.') }}</li>
        <li class="mb-1"><strong>{{ __('Churn') }}</strong> {{ __('- un taux élevé signale un problème de rétention à investiguer.') }}</li>
        <li><strong>{{ __('Plans') }}</strong> {{ __('- gérez vos plans tarifaires dans la section Plans de l\'administration.') }}</li>
    </ul>
</div>
