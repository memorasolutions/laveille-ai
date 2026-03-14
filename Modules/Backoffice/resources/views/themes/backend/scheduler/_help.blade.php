<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Le <strong>planificateur de tâches</strong> automatise des opérations récurrentes
        sans intervention manuelle. Les tâches s'<strong>exécutent en arrière-plan</strong>
        selon un calendrier que vous définissez.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="clock" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fréquences disponibles') }}
    </h6>
    <p class="text-muted small mb-0">
        Vous pouvez planifier une tâche à la fréquence de votre choix :
        <strong>chaque minute</strong>, toutes les <strong>heures</strong>, chaque
        <strong>jour</strong>, chaque <strong>semaine</strong>, chaque <strong>mois</strong>,
        ou via une <strong>expression cron personnalisée</strong> pour une précision totale.
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
            <code class="badge bg-secondary bg-opacity-10 text-secondary mt-1" style="font-size:10px;">0 2 * * *</code>
            <div>
                <strong class="small">{{ __('Sauvegarde quotidienne') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Sauvegarde automatique de la base de données chaque nuit à 2h.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <code class="badge bg-secondary bg-opacity-10 text-secondary mt-1" style="font-size:10px;">0 0 * * 0</code>
            <div>
                <strong class="small">{{ __('Purge hebdomadaire') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Nettoyage des logs et fichiers temporaires chaque dimanche.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <code class="badge bg-secondary bg-opacity-10 text-secondary mt-1" style="font-size:10px;">0 8 1 * *</code>
            <div>
                <strong class="small">{{ __('Rapport mensuel') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Envoi automatique d\'un rapport le 1er de chaque mois à 8h.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layers" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Tâches système vs personnalisées') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Système') }}</strong> {{ __('- définies dans le code, non modifiables depuis l\'interface.') }}</li>
        <li><strong>{{ __('Personnalisées') }}</strong> {{ __('- créées ici, modifiables et activables/désactivables à la volée.') }}</li>
    </ul>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Prérequis') }}</strong> {{ __('- le cron système') }} <code>* * * * * php artisan schedule:run</code> {{ __('doit être configuré sur le serveur.') }}</li>
        <li class="mb-1"><strong>{{ __('Logs') }}</strong> {{ __('- vérifiez les logs applicatifs si une tâche ne s\'exécute pas comme prévu.') }}</li>
        <li><strong>{{ __('Désactivation') }}</strong> {{ __('- vous pouvez suspendre une tâche sans la supprimer via le bouton pause.') }}</li>
    </ul>
</div>
