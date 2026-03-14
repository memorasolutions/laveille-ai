<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Une') }} <strong>{{ __('sauvegarde') }}</strong> {{ __('est une copie complète de votre base de données et de vos fichiers à un instant T. En cas de problème (erreur humaine, panne, piratage), elle vous permet de') }} <strong>{{ __('tout restaurer') }}</strong> {{ __('rapidement.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="refresh-cw" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Lancer une sauvegarde') }}</strong> {{ __('pour en créer une manuellement.') }}</li>
        <li class="mb-1">{{ __('La sauvegarde s\'exécute en') }} <strong>{{ __('arrière-plan') }}</strong> {{ __('via la file d\'attente Laravel.') }}</li>
        <li class="mb-1">{{ __('Une fois terminée, le fichier apparaît dans la liste avec sa taille et sa date.') }}</li>
        <li>{{ __('Téléchargez le fichier ou supprimez-le depuis le menu Actions.') }}</li>
    </ol>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-danger" style="width:16px;height:16px;"></i>
        {{ __('Que faire en cas de problème ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Urgence') }}</span>
            <div>
                <strong class="small">{{ __('Site cassé ou données perdues') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Téléchargez la sauvegarde la plus récente et restaurez-la sur votre serveur.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning mt-1">{{ __('Précaution') }}</span>
            <div>
                <strong class="small">{{ __('Avant une mise à jour majeure') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Lancez toujours une sauvegarde manuelle avant d\'effectuer des modifications importantes.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Gestion des fichiers') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cochez plusieurs sauvegardes pour les') }} <strong>{{ __('supprimer en lot') }}</strong>.</li>
        <li class="mb-1">{{ __('Chaque ligne affiche le nom du fichier, sa taille et sa date de création.') }}</li>
        <li>{{ __('Le menu') }} <strong>{{ __('Actions') }}</strong> {{ __('(⋮) permet de télécharger ou supprimer une sauvegarde individuelle.') }}</li>
    </ul>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Testez') }}</strong> {{ __('régulièrement vos sauvegardes en les restaurant dans un environnement de test.') }}</li>
        <li class="mb-1"><strong>{{ __('Conservez') }}</strong> {{ __('plusieurs sauvegardes à des dates différentes (règle 3-2-1).') }}</li>
        <li>{{ __('Une sauvegarde non testée n\'est pas une sauvegarde fiable.') }}</li>
    </ul>
</div>
