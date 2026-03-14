<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Les <strong>rôles</strong> définissent <strong>qui peut faire quoi</strong> dans
        l'administration. Chaque utilisateur hérite des permissions associées à son rôle,
        ce qui simplifie la gestion des accès à grande échelle.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="users" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Rôles par défaut') }}
    </h6>
    <p class="text-muted small mb-0">
        L'application inclut quatre rôles préconfigurés :<br>
        <strong>Super admin</strong> - accès total à tout sans restriction.<br>
        <strong>Admin</strong> - accès complet sauf la gestion des rôles.<br>
        <strong>Éditeur</strong> - gestion du contenu uniquement (articles, pages, FAQ).<br>
        <strong>Utilisateur</strong> - accès limité à son propre profil.
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">1</span>
            <div>
                <strong class="small">{{ __('Créer ou modifier un rôle') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Définissez le nom du rôle et cochez les permissions à lui accorder.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">2</span>
            <div>
                <strong class="small">{{ __('Assigner à un utilisateur') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Dans la fiche utilisateur, sélectionnez le rôle souhaité.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">3</span>
            <div>
                <strong class="small">{{ __('Effet immédiat') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Les permissions s\'appliquent dès la prochaine requête de l\'utilisateur.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="key" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Permissions disponibles') }}
    </h6>
    <p class="text-muted small mb-2">{{ __('Les permissions couvrent toutes les ressources de l\'application :') }}</p>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Utilisateurs') }}</strong> {{ __(': voir, créer, modifier, supprimer.') }}</li>
        <li class="mb-1"><strong>{{ __('Contenu') }}</strong> {{ __(': articles, pages, FAQ, médias.') }}</li>
        <li><strong>{{ __('Système') }}</strong> {{ __(': paramètres, sauvegardes, logs, sécurité.') }}</li>
    </ul>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(239,68,68,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-danger" style="width:16px;height:16px;"></i>
        {{ __('Attention') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Ne modifiez pas') }}</strong> {{ __('le rôle Super admin - il doit toujours conserver tous les accès.') }}</li>
        <li class="mb-1"><strong>{{ __('Suppression') }}</strong> {{ __('d\'un rôle : les utilisateurs concernés perdent leurs accès.') }}</li>
        <li><strong>{{ __('Principe du moindre privilège') }}</strong> {{ __('- accordez uniquement les permissions nécessaires.') }}</li>
    </ul>
</div>
