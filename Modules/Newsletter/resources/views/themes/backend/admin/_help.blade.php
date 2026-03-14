<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Liste de tous vos') }} <strong>{{ __('abonnés newsletter') }}</strong>.
        {{ __('Consultez, exportez, importez et segmentez votre liste de contacts.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="activity" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Statuts des abonnés') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Actif') }}</span>
            <p class="text-muted small mb-0">{{ __('L\'abonné reçoit vos emails normalement.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-secondary mt-1">{{ __('Désabonné') }}</span>
            <p class="text-muted small mb-0">{{ __('L\'abonné a demandé à ne plus recevoir d\'emails.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Rebond') }}</span>
            <p class="text-muted small mb-0">{{ __('L\'adresse email est invalide ou inexistante - les envois échouent.') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Voir') }}</strong> {{ __('la liste complète avec statut et date d\'inscription.') }}</li>
        <li class="mb-1"><strong>{{ __('Exporter') }}</strong> {{ __('vos abonnés en CSV pour une utilisation externe.') }}</li>
        <li class="mb-1"><strong>{{ __('Importer') }}</strong> {{ __('une liste existante via fichier CSV.') }}</li>
        <li><strong>{{ __('Segmenter') }}</strong> {{ __('en filtrant par statut, date ou source d\'inscription.') }}</li>
    </ul>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('RGPD') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('L\'abonnement est basé sur le') }} <strong>{{ __('consentement explicite') }}</strong> {{ __('(double opt-in). Chaque abonné a confirmé son adresse email avant d\'être activé. Vous êtes responsable de la conservation et de l\'utilisation de ces données.') }}
    </p>
</div>
