<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('La page <strong>Thèmes</strong> gère l\'<strong>apparence visuelle</strong> de votre backoffice. Choisissez et activez le thème qui correspond à vos préférences ou à votre charte graphique.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="palette" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Choix du thème') }}</strong> – {{ __('sélectionnez parmi les thèmes disponibles') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Aperçu visuel') }}</strong> – {{ __('voyez l\'apparence avant d\'activer') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Configuration') }}</strong> – {{ __('via fichier .env ou config/backoffice.php') }}</p>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('Activez un thème et il s\'applique <strong>immédiatement</strong> à toutes les pages du backoffice. Le thème actif est indiqué par un badge vert. Pour une personnalisation plus fine (couleurs, polices, logos), utilisez la page <em>Identité visuelle</em>.') !!}
    </p>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('Un <strong>seul thème peut être actif</strong> à la fois. Le thème actuel est <strong>Backend (NobleUI)</strong>, basé sur Bootstrap 5.3.8 avec une sidebar sombre et les icônes Lucide.') !!}
    </p>
</div>
