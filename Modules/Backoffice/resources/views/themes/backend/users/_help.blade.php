<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        La page <strong>Utilisateurs</strong> centralise la gestion de tous les <strong>comptes de votre application</strong> :
        création, modification, désactivation et attribution des rôles.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="users" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Créer') }}</strong> – {{ __('ajouter manuellement un compte utilisateur') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Modifier') }}</strong> – {{ __('mettre à jour les informations et le rôle') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Désactiver') }}</strong> – {{ __('bloquer l\'accès sans supprimer les données') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Exporter / Importer') }}</strong> – {{ __('CSV pour les transferts en masse') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="user-check" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Informations visibles') }}
    </h6>
    <p class="text-muted small mb-0">
        Pour chaque utilisateur : <strong>{{ __('nom') }}</strong>, <strong>{{ __('email') }}</strong>,
        <strong>{{ __('rôle') }}</strong>, <strong>{{ __('date d\'inscription') }}</strong>
        et <strong>{{ __('dernière connexion') }}</strong>.
        Filtrez et recherchez facilement dans la liste.
    </p>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        <strong>{{ __('Désactiver un compte est préférable à le supprimer') }}</strong> :
        cela conserve l'historique des actions et permet la réactivation ultérieure.
        La suppression efface définitivement toutes les données associées.
    </p>
</div>
