<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('Les <strong>notifications push</strong> vous permettent d\'<strong>envoyer des messages directement sur le navigateur ou le téléphone</strong> de vos utilisateurs, même quand ils ne sont pas sur votre site.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="bell" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Prérequis') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('Pour recevoir une notification push, l\'utilisateur doit d\'abord avoir <strong>accepté les notifications</strong> dans son navigateur. Une fois abonné, il apparaît dans le compteur d\'abonnements actifs.') !!}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Cas d\'usage') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Promo') }}</span>
            <div>
                <strong class="small">{{ __('Offre limitée') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Informez vos utilisateurs d\'une promotion en cours.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Alerte') }}</span>
            <div>
                <strong class="small">{{ __('Alerte importante') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Signalez une interruption de service ou une information critique.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Nouveauté') }}</span>
            <div>
                <strong class="small">{{ __('Nouvelle fonctionnalité') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Annoncez une mise à jour ou une nouvelle fonctionnalité.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment envoyer ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{!! __('Saisissez un <strong>titre</strong> et un <strong>message</strong> (500 caractères max).') !!}</li>
        <li class="mb-1">{!! __('Ajoutez un <strong>lien</strong> optionnel vers une page de votre site.') !!}</li>
        <li class="mb-1">{!! __('Choisissez les <strong>destinataires</strong> (tous les utilisateurs ou un rôle spécifique).') !!}</li>
        <li>{!! __('Cliquez sur <strong>Envoyer</strong> - la notification est distribuée immédiatement.') !!}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{!! __('<strong>N\'abusez pas</strong> - trop de notifications pousse les utilisateurs à se désabonner.') !!}</li>
        <li class="mb-1">{!! __('<strong>Soyez concis</strong> - un titre accrocheur et un message court sont plus efficaces.') !!}</li>
        <li>{!! __('<strong>Ciblage</strong> - filtrez par rôle pour envoyer des messages pertinents à chaque groupe.') !!}</li>
    </ul>
</div>
