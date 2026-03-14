<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Le <strong>{{ __('journal d\'activité') }}</strong> {{ __('enregistre automatiquement toutes les actions importantes effectuées dans l\'administration : connexions, créations, modifications et suppressions. C\'est votre') }} <strong>{{ __('mémoire de sécurité') }}</strong>.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="table" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Que voit-on dans ce tableau ?') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Utilisateur') }}</strong> {{ __('– qui a effectué l\'action') }}</li>
        <li class="mb-1"><strong>{{ __('Action') }}</strong> {{ __('– ce qui a été fait (ex. : article créé, utilisateur supprimé)') }}</li>
        <li class="mb-1"><strong>{{ __('Date') }}</strong> {{ __('– quand l\'action s\'est produite') }}</li>
        <li><strong>{{ __('Adresse IP') }}</strong> {{ __('– depuis quelle connexion') }}</li>
    </ul>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="shield-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Pourquoi c\'est important ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Audit') }}</span>
            <div>
                <strong class="small">{{ __('Conformité RGPD') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Vous pouvez prouver qui a accédé ou modifié des données personnelles.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning mt-1">{{ __('Sécurité') }}</span>
            <div>
                <strong class="small">{{ __('Détection d\'anomalies') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Repérez rapidement une action suspecte ou non autorisée.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Traçabilité') }}</span>
            <div>
                <strong class="small">{{ __('Historique complet') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Retrouvez qui a supprimé ou modifié quoi, et à quel moment.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="download" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Exporter et purger') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Exporter CSV') }}</strong> {{ __('pour télécharger l\'historique complet.') }}</li>
        <li class="mb-1">{{ __('Le bouton') }} <strong>{{ __('Purger (+30j)') }}</strong> {{ __('supprime les entrées de plus de 30 jours pour alléger la base.') }}</li>
        <li>{{ __('La purge est irréversible – exportez avant si vous souhaitez garder une archive.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Les logs sont enregistrés') }} <strong>{{ __('automatiquement') }}</strong>, {{ __('aucune configuration n\'est requise.') }}</li>
        <li class="mb-1">{{ __('Ils sont') }} <strong>{{ __('non modifiables') }}</strong> {{ __('– même les administrateurs ne peuvent pas les altérer.') }}</li>
        <li>{{ __('Un log manquant peut indiquer une action effectuée directement en base de données.') }}</li>
    </ul>
</div>
