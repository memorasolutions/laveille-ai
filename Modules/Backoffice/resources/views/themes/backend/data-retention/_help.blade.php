<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('La <strong>rétention des données</strong> définit combien de temps chaque type de donnée personnelle est conservé dans votre application. Le RGPD interdit de conserver les données <strong>indéfiniment</strong>.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="clock" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Obligation RGPD') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Les données personnelles doivent être effacées ou anonymisées dès qu\'elles ne sont plus nécessaires à leur finalité.') }}
        {{ __('Cette page vous donne une vue d\'ensemble des tables surveillées et du nombre d\'enregistrements éligibles au nettoyage.') }}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Consultez le tableau pour voir les tables surveillées et leur durée de rétention configurée.') }}</li>
        <li class="mb-1">{!! __('La colonne <strong>Éligibles</strong> indique le nombre d\'enregistrements à supprimer.') !!}</li>
        <li class="mb-1">{{ __('Lancez la purge avec la commande') }} <code>php artisan app:cleanup</code>.</li>
        <li>{{ __('Utilisez') }} <code>--dry-run</code> {{ __('pour simuler sans supprimer.') }}</li>
    </ol>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">OK</span>
            <p class="text-muted small mb-0">{{ __('Statut vert : aucun enregistrement à purger dans cette table.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('À surveiller') }}</span>
            <p class="text-muted small mb-0">{{ __('Quelques enregistrements éligibles, planifiez un nettoyage prochainement.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Nettoyage requis') }}</span>
            <p class="text-muted small mb-0">{{ __('Plus de 100 enregistrements éligibles, action immédiate recommandée.') }}</p>
        </div>
    </div>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Données anonymisées') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Les données anonymisées (sans lien possible vers une personne) ne sont pas soumises aux règles de rétention RGPD et peuvent être conservées indéfiniment à des fins statistiques.') }}
    </p>
</div>
