<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Un <strong>{{ __('test A/B') }}</strong> {{ __('vous permet de comparer deux variantes d\'une page pour déterminer laquelle') }}
        <strong>{{ __('performe le mieux') }}</strong> {{ __('auprès de vos visiteurs, sans conjectures.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shuffle" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Le système répartit les visiteurs aléatoirement entre vos variantes (A, B, voire C). Chaque groupe voit une version différente. Les métriques s\'accumulent jusqu\'à désigner une variante gagnante.') }}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="bar-chart-2" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Métriques suivies') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Conversion') }}</span>
            <div>
                <strong class="small">{{ __('Taux de conversion') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Pourcentage de visiteurs ayant réalisé l\'action cible (achat, inscription, clic).') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Participants') }}</span>
            <div>
                <strong class="small">{{ __('Nombre de participants') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Nombre total de visiteurs exposés à chaque variante.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Gagnant') }}</span>
            <div>
                <strong class="small">{{ __('Variante gagnante') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('La variante avec la meilleure performance statistique.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Durée minimale') }}</strong> {{ __(':') }} {{ __('laissez tourner un test au moins 2 semaines pour des résultats fiables.') }}</li>
        <li class="mb-1"><strong>{{ __('Une variable à la fois') }}</strong> {{ __(':') }} {{ __('ne changez qu\'un seul élément entre les variantes pour des données exploitables.') }}</li>
        <li><strong>{{ __('Trafic suffisant') }}</strong> {{ __(':') }} {{ __('un minimum de 100 visiteurs par variante est recommandé pour une signification statistique.') }}</li>
    </ul>
</div>
