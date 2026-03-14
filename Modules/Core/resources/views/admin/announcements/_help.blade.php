<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Les') }} <strong>{{ __('annonces') }}</strong> {{ __('sont des bannières affichées en haut du backoffice pour') }}
        <strong>{{ __('informer les utilisateurs') }}</strong> {{ __('d\'une nouveauté, d\'une maintenance ou d\'un avertissement.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Créer') }}</strong> {{ __(':') }} {{ __('rédigez une annonce avec titre, contenu et type (info, warning, danger).') }}</li>
        <li class="mb-1"><strong>{{ __('Planifier') }}</strong> {{ __(':') }} {{ __('définissez une date de publication et d\'expiration optionnelle.') }}</li>
        <li><strong>{{ __('Cibler') }}</strong> {{ __(':') }} {{ __('affichez l\'annonce à tous les utilisateurs ou à un rôle spécifique.') }}</li>
    </ul>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Info') }}</span>
            <div>
                <strong class="small">{{ __('Niveau informatif') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Annonce neutre pour communiquer une nouveauté ou un rappel.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('Warning') }}</span>
            <div>
                <strong class="small">{{ __('Niveau avertissement') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Signale une situation à surveiller ou une action recommandée.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Danger') }}</span>
            <div>
                <strong class="small">{{ __('Niveau critique') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Alerte urgente requérant une attention immédiate.') }}</p>
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
        <li class="mb-1">{{ __('Les utilisateurs peuvent') }} <strong>{{ __('fermer une annonce') }}</strong>{{ __(' ; elle ne réapparaîtra plus pour eux.') }}</li>
        <li class="mb-1">{{ __('Une annonce non publiée reste en brouillon et n\'est visible que des administrateurs.') }}</li>
        <li>{{ __('Le changelog (type Nouveauté / Amélioration / Correctif) sert à documenter l\'évolution du produit.') }}</li>
    </ul>
</div>
