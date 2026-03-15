<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('La page <strong>Informations système</strong> affiche tous les détails techniques de votre environnement serveur : versions, extensions, ressources disponibles et état de la configuration.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="server" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Informations affichées') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-primary mt-1">PHP</span>
            <p class="text-muted small mb-0">{{ __('Version, SAPI, limites mémoire et upload, OPcache') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-success mt-1">Laravel</span>
            <p class="text-muted small mb-0">{{ __('Version, environnement, drivers cache/session/queue') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-warning mt-1">{{ __('Serveur') }}</span>
            <p class="text-muted small mb-0">{{ __('OS, hostname, espace disque disponible') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <span class="badge bg-info mt-1">{{ __('Modules') }}</span>
            <p class="text-muted small mb-0">{{ __('Liste de tous les modules actifs de l\'application') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="stethoscope" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Pourquoi c\'est utile ?') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('Cette page permet de <strong>diagnostiquer des problèmes</strong> : extension PHP manquante, limite mémoire trop basse, espace disque insuffisant. Elle aide aussi à vérifier la <strong>compatibilité</strong> avant une mise à jour.') !!}
    </p>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="headphones" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('En cas de problème, <strong>partagez cette page avec le support technique</strong>. Elle contient toutes les informations nécessaires pour un diagnostic rapide, sans avoir à accéder au serveur directement.') !!}
    </p>
</div>
