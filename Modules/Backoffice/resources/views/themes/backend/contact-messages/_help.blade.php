<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Cette page regroupe tous les') }} <strong>{{ __('messages envoyés par vos visiteurs') }}</strong> {{ __('via le formulaire de contact de votre site. Chaque message est conservé ici et vous pouvez le consulter, le marquer comme lu ou y répondre.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités disponibles') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Statut lu/non lu') }}</strong> {{ __('– les messages non lus apparaissent en gras avec un point bleu.') }}</li>
        <li class="mb-1"><strong>{{ __('Filtres') }}</strong> {{ __('– filtrez par statut (lu/non lu) ou recherchez par nom, email ou sujet.') }}</li>
        <li class="mb-1"><strong>{{ __('Détail') }}</strong> {{ __('– cliquez sur l\'icône œil pour lire le message complet.') }}</li>
        <li><strong>{{ __('Suppression') }}</strong> {{ __('– supprimez les messages dont vous n\'avez plus besoin.') }}</li>
    </ul>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clock" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Nouveau') }}</span>
            <div>
                <strong class="small">{{ __('Badge de comptage') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Le nombre de messages non lus est affiché dans le titre de la page pour que vous ne manquiez rien.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Lu') }}</span>
            <div>
                <strong class="small">{{ __('Marquage automatique') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Un message est automatiquement marqué comme "lu" dès que vous ouvrez son détail.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="zap" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Répondez aux messages rapidement – un délai de réponse de moins de 24h améliore significativement l\'expérience de vos visiteurs et leur confiance envers votre service. Utilisez les filtres pour traiter en priorité les messages non lus.') }}
    </p>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Les messages sont stockés en base de données –') }} <strong>{{ __('aucun email n\'est perdu') }}</strong> {{ __('même si votre boite mail est pleine.') }}</li>
        <li class="mb-1">{{ __('La suppression d\'un message est') }} <strong>{{ __('définitive') }}</strong> {{ __('– confirmez bien avant de supprimer.') }}</li>
        <li>{{ __('Utilisez la recherche pour retrouver rapidement un message précis parmi des dizaines.') }}</li>
    </ul>
</div>
