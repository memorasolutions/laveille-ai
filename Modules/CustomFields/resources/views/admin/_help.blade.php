<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Les <strong>Champs personnalisés</strong> vous permettent d'<strong>ajouter des champs supplémentaires à n'importe quel formulaire</strong>
        de l'application (articles, pages, utilisateurs...) sans modifier le code.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="sliders-horizontal" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Types de champs disponibles') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Texte court, texte long, nombre, date, liste déroulante, case à cocher (oui/non), URL, et fichier. Chaque type génère automatiquement le bon composant de formulaire.') }}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="workflow" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Étape 1') }}</span>
            <div>
                <strong class="small">{{ __('Créez le champ') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Définissez le nom, la clé unique, le type et le modèle cible (article, page, etc.).') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Étape 2') }}</span>
            <div>
                <strong class="small">{{ __('Apparition automatique') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Le champ apparaît automatiquement dans les formulaires du modèle ciblé (création et édition).') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Étape 3') }}</span>
            <div>
                <strong class="small">{{ __('Données stockées') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Les valeurs saisies sont enregistrées en base de données et accessibles via l\'API ou les templates.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Options de configuration') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Marquez un champ comme') }} <strong>{{ __('requis') }}</strong> {{ __('pour le rendre obligatoire dans les formulaires.') }}</li>
        <li class="mb-1">{{ __('Désactivez un champ sans le supprimer pour le masquer temporairement.') }}</li>
        <li class="mb-1">{{ __('La') }} <strong>{{ __('clé') }}</strong> {{ __('(slug) identifie le champ de façon unique dans le code.') }}</li>
        <li>{{ __('Les champs sont regroupés par modèle cible pour une meilleure lisibilité.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="database" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Les données des champs personnalisés sont stockées séparément et sont exportables. Supprimer une définition de champ ne supprime pas les données déjà enregistrées pour les entrées existantes.') }}
    </p>
</div>
