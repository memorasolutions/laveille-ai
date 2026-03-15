<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Suivez vos <strong>KPIs</strong> : chiffre d'affaires, commandes, produits et stocks faibles en un coup d'œil.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="bar-chart-2" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('KPIs essentiels') }}
    </h6>
    <p class="text-muted small mb-0">
        Visualisez votre <strong>chiffre d'affaires</strong>, nombre de <strong>commandes</strong>,
        <strong>produits</strong> actifs et alertes de <strong>stocks faibles</strong>.<br>
        Données mises à jour en temps réel.
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
                <strong class="small">{{ __('Consulter les KPIs') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Tableau de bord principal avec indicateurs clés.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">2</span>
            <div>
                <strong class="small">{{ __('Vérifier les stocks') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Alertes automatiques pour les stocks faibles.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">3</span>
            <div>
                <strong class="small">{{ __('Exporter les rapports') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Téléchargez les données pour analyse approfondie.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="zap" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuces') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Consultez le tableau de bord quotidiennement pour détecter les tendances.') }}</li>
        <li class="mb-1">{{ __('Configurez des seuils d\'alertes pour les stocks critiques.') }}</li>
        <li>{{ __('Comparez les données avec la période précédente.') }}</li>
    </ul>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(239,68,68,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-danger" style="width:16px;height:16px;"></i>
        {{ __('Attention') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Les données sont actualisées en temps réel à chaque chargement de page.') }}</li>
    </ul>
</div>
