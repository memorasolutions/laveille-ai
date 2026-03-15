<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('Le module <strong>Traductions</strong> gère le <strong>multilinguisme</strong> de votre application. Ajoutez des langues, traduisez les textes et proposez une expérience localisée à vos visiteurs.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="languages" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Ajouter des langues') }}</strong> – {{ __('français, anglais, espagnol, etc.') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Traduire les textes') }}</strong> – {{ __('interface, contenus, emails') }}</p>
        </div>
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="check" class="text-success flex-shrink-0" style="width:14px;height:14px;margin-top:2px;"></i>
            <p class="text-muted small mb-0"><strong>{{ __('Importer / Exporter') }}</strong> – {{ __('fichiers JSON ou PHP') }}</p>
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
        {!! __('Chaque texte de l\'application possède une <strong>clé de traduction</strong>. Pour chaque langue active, vous associez une valeur à cette clé. Laravel choisit automatiquement la bonne traduction selon la langue de l\'utilisateur.') !!}
    </p>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="star" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <p class="text-muted small mb-0">
        {!! __('<strong>Exportez vos traductions</strong> pour les faire relire par un traducteur avant publication. Vous pouvez ensuite réimporter le fichier corrigé sans ressaisir manuellement chaque texte.') !!}
    </p>
</div>
