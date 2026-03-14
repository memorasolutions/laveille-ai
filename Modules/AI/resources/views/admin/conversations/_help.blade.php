<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Cette section présente l\'') }}<strong>{{ __('historique complet des conversations') }}</strong>
        {{ __('menées avec l\'assistant IA. Consultez, recherchez ou supprimez les échanges passés.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Consulter') }}</strong> {{ __(':') }} {{ __('visualisez le contenu complet d\'une conversation message par message.') }}</li>
        <li class="mb-1"><strong>{{ __('Rechercher') }}</strong> {{ __(':') }} {{ __('filtrez par statut, date de début ou date de fin.') }}</li>
        <li><strong>{{ __('Supprimer') }}</strong> {{ __(':') }} {{ __('fermez ou supprimez les conversations archivées.') }}</li>
    </ul>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="cpu" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Modèles utilisés') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">GPT</span>
            <div>
                <strong class="small">GPT-4o / GPT-4o mini</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Modèles OpenAI, polyvalents et performants pour la majorité des tâches.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('Open') }}</span>
            <div>
                <strong class="small">DeepSeek / Claude</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Modèles alternatifs accessibles via OpenRouter selon la configuration.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Les conversations sont') }} <strong>{{ __('privées') }}</strong> {{ __('et liées au compte de l\'utilisateur qui les a initiées.') }}</li>
        <li class="mb-1">{{ __('Le compteur de') }} <strong>{{ __('tokens') }}</strong> {{ __('vous aide à estimer le coût de chaque conversation.') }}</li>
        <li>{{ __('Une conversation fermée n\'est plus accessible à l\'utilisateur mais reste visible dans cet historique.') }}</li>
    </ul>
</div>
