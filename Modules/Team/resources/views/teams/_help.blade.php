<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Le module <strong>Équipes</strong> gère les <strong>organisations et équipes multi-utilisateurs</strong>.
        Il permet de regrouper des utilisateurs dans des espaces de travail distincts avec des rôles dédiés.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="user-plus" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Créez des équipes, invitez des membres par e-mail et attribuez des rôles d\'équipe (propriétaire, membre). Chaque équipe dispose de son propre espace et de ses propres données.') }}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="git-branch" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Multi') }}</span>
            <div>
                <strong class="small">{{ __('Appartenance multiple') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Chaque utilisateur peut appartenir à plusieurs équipes simultanément et basculer entre elles selon le contexte.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('Rôles') }}</span>
            <div>
                <strong class="small">{{ __('Rôles d\'équipe') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Chaque membre a un rôle au sein de l\'équipe (propriétaire, admin, membre) indépendamment de son rôle global sur l\'application.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Créer une équipe') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Nouvelle équipe') }}</strong> {{ __('pour créer un nouvel espace.') }}</li>
        <li class="mb-1">{{ __('Donnez un nom et une description à votre équipe.') }}</li>
        <li class="mb-1">{{ __('Invitez des membres via leur adresse e-mail.') }}</li>
        <li>{{ __('Attribuez les rôles appropriés à chaque membre.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="crown" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Le propriétaire d\'une équipe a tous les droits sur celle-ci : il peut inviter ou retirer des membres, modifier les rôles et supprimer l\'équipe. Seul un super-administrateur peut modifier le propriétaire depuis cette interface.') }}
    </p>
</div>
