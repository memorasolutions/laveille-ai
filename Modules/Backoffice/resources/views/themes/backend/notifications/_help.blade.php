<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Le <strong>{{ __('centre de notifications') }}</strong> vous permet d'envoyer des alertes
        et messages à vos utilisateurs, et de consulter l'historique de toutes les notifications
        générées par l'application.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="layers" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Types de notifications') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" style="min-width:110px;">{{ __('Information') }}</span>
            <span class="text-muted small">{{ __('Annonce générale, mise à jour, actualité') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark" style="min-width:110px;">{{ __('Avertissement') }}</span>
            <span class="text-muted small">{{ __('Situation nécessitant l\'attention de l\'utilisateur') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger" style="min-width:110px;">{{ __('Critique') }}</span>
            <span class="text-muted small">{{ __('Alerte de sécurité ou action urgente requise') }}</span>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment diffuser une alerte ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Choisissez le') }} <strong>{{ __('niveau') }}</strong> {{ __('(Information, Avertissement ou Critique).') }}</li>
        <li class="mb-1">{{ __('Rédigez votre') }} <strong>{{ __('message') }}</strong> {{ __('dans le champ prévu.') }}</li>
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Diffuser') }}</strong> {{ __('pour envoyer à tous les utilisateurs connectés.') }}</li>
        <li>{{ __('La notification apparaît instantanément dans la cloche en haut de l\'interface.') }}</li>
    </ol>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="smartphone" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Les') }} <strong>{{ __('notifications push') }}</strong>
        {{ __('(vers le navigateur ou mobile) nécessitent que l\'utilisateur ait préalablement') }}
        <strong>{{ __('accepté les notifications') }}</strong>
        {{ __('dans son navigateur. Les notifications intégrées à l\'application fonctionnent sans autorisation.') }}
    </p>
</div>
