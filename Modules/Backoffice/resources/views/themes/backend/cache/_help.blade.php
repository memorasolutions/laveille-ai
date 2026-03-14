<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Le') }} <strong>{{ __('cache') }}</strong> {{ __('est une mémoire temporaire qui stocke les données fréquemment utilisées pour éviter de les recalculer à chaque requête. Il') }} <strong>{{ __('accélère votre site') }}</strong> {{ __('de manière significative.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layers" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Les 4 types de cache') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Cache applicatif') }}</strong> {{ __('– données mises en cache par votre code (Redis ou fichiers)') }}</li>
        <li class="mb-1"><strong>{{ __('Configuration') }}</strong> {{ __('– les fichiers config/ fusionnés en un seul fichier optimisé') }}</li>
        <li class="mb-1"><strong>{{ __('Vues compilées') }}</strong> {{ __('– les templates Blade convertis en PHP natif') }}</li>
        <li><strong>{{ __('Routes') }}</strong> {{ __('– le registre de toutes les URLs de l\'application') }}</li>
    </ul>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clock" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Quand vider le cache ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Après') }}</span>
            <div>
                <strong class="small">{{ __('Une mise à jour ou déploiement') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Videz tous les caches pour que les nouvelles modifications soient bien prises en compte.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning mt-1">{{ __('Si') }}</span>
            <div>
                <strong class="small">{{ __('Des données semblent obsolètes') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Un paramètre modifié n\'est pas reflété ? Videz le cache applicatif.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Si') }}</span>
            <div>
                <strong class="small">{{ __('Une vue ne se met pas à jour') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Videz les vues compilées pour forcer la recompilation des templates Blade.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment l\'utiliser ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Vider') }}</strong> {{ __('pour effacer un cache spécifique.') }}</li>
        <li class="mb-1">{{ __('Ou cliquez sur') }} <strong>{{ __('Vider tous les caches') }}</strong> {{ __('(bouton rouge) pour tout effacer en une seule action.') }}</li>
        <li>{{ __('Le cache se reconstituera automatiquement au fur et à mesure des prochaines requêtes.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Vider le cache peut') }} <strong>{{ __('ralentir brièvement le site') }}</strong> {{ __('le temps qu\'il se reconstitue – c\'est normal.') }}</li>
        <li class="mb-1">{{ __('Le cache applicatif utilise') }} <strong>{{ __('Redis') }}</strong> {{ __('si configuré, sinon le système de fichiers.') }}</li>
        <li>{{ __('En cas de doute, commencez par vider uniquement le cache applicatif avant de tout vider.') }}</li>
    </ul>
</div>
