<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('Les <strong>Webhooks</strong> sont des <strong>notifications automatiques</strong> envoyées à des services externes quand un événement survient dans votre application. C\'est le moyen de connecter votre app au reste du monde.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="webhook" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Un événement survient (ex : nouvel utilisateur inscrit).') }}</li>
        <li class="mb-1">{{ __('Votre application envoie automatiquement une') }} <strong>{{ __('requête HTTP POST') }}</strong> {{ __('à l\'URL configurée.') }}</li>
        <li>{{ __('Le service destinataire reçoit les données et peut réagir (notification Slack, mise à jour CRM...).') }}</li>
    </ol>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="plug" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Cas d\'usage') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>Slack</strong> – {{ __('notification d\'équipe sur un nouvel événement') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>CRM</strong> – {{ __('synchronisation automatique des contacts') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>Zapier / Make</strong> – {{ __('automatisations sans code') }}</p>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('Vérifiez que l\'<strong>URL de destination est accessible</strong> et répond avec un code HTTP 200. Les webhooks en échec sont consignés dans les logs. Consultez la page <em>Statistiques</em> pour voir le taux de succès.') !!}
    </p>
</div>
