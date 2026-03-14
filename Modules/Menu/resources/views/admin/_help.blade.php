<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Les') }} <strong>{{ __('menus de navigation') }}</strong> {{ __('définissent la structure de navigation visible sur votre site.') }}
        {{ __('Gérez-les ici et') }} <strong>{{ __('assignez-les à un emplacement') }}</strong> {{ __('(header, footer, sidebar).') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Créer des menus') }}</strong> {{ __(':') }} {{ __('nommez un menu et assignez-lui un emplacement sur le site.') }}</li>
        <li class="mb-1"><strong>{{ __('Ajouter des liens') }}</strong> {{ __(':') }} {{ __('ajoutez autant d\'éléments que nécessaire à chaque menu.') }}</li>
        <li><strong>{{ __('Glisser-déposer') }}</strong> {{ __(':') }} {{ __('réorganisez l\'ordre des éléments par simple glisser-déposer.') }}</li>
    </ul>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="link" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Types de liens') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Interne') }}</span>
            <div>
                <strong class="small">{{ __('Page interne') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Lien vers une page de votre site générée par le CMS.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('Externe') }}</span>
            <div>
                <strong class="small">{{ __('URL externe') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Lien vers un site tiers, ouvert dans un nouvel onglet.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Blog') }}</span>
            <div>
                <strong class="small">{{ __('Catégorie de blog / Page statique') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Lien dynamique mis à jour automatiquement si le slug change.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce ergonomie') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Limitez les niveaux de sous-menus à') }} <strong>{{ __('2 à 3 niveaux maximum') }}</strong> {{ __('pour une bonne expérience utilisateur.') }}</li>
        <li class="mb-1">{{ __('Un menu inactif reste en base de données mais n\'est pas affiché sur le site.') }}</li>
        <li>{{ __('Le même menu peut être réutilisé à plusieurs emplacements si le thème le supporte.') }}</li>
    </ul>
</div>
