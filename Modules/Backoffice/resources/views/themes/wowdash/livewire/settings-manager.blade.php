@php
$helpTextsData = [
    'ai.openrouter_api_key'  => 'Votre clé API OpenRouter. Créez un compte gratuit sur openrouter.ai, puis copiez votre clé depuis le tableau de bord. Les modèles gratuits ne coûtent rien.',
    'ai.default_model'       => "Le modèle IA utilisé par défaut quand aucun modèle spécifique n'est défini pour une tâche. Choisissez un modèle gratuit pour commencer.",
    'ai.chatbot_model'       => 'Le modèle utilisé pour le chatbot intégré au site. Un modèle conversationnel comme Llama est recommandé.',
    'ai.content_model'       => "Le modèle utilisé pour générer du contenu (articles, résumés). Un modèle orienté code/contenu fonctionne bien.",
    'ai.moderation_model'    => 'Le modèle qui analyse les commentaires et contenus soumis pour détecter le spam ou les messages toxiques.',
    'ai.seo_model'           => 'Le modèle qui génère les balises meta SEO (titre, description) pour vos articles et pages.',
    'ai.translation_model'   => 'Le modèle utilisé pour la traduction automatique des clés de traduction. Llama 3.3 gère bien le français, anglais, espagnol, allemand.',
    'ai.temperature'         => 'Contrôle la créativité des réponses IA (0 = précis et déterministe, 2 = très créatif). Pour la traduction, gardez 0.3-0.5. Pour le contenu, 0.7 est un bon équilibre.',
    'ai.max_tokens'          => "Nombre maximum de mots/tokens que l'IA peut générer par réponse. 2048 convient pour la plupart des usages. Augmentez pour les articles longs.",
    'ai.chatbot_enabled'     => 'Active ou désactive le chatbot IA visible sur votre site public.',
    'ai.chatbot_system_prompt' => "Les instructions données au chatbot pour définir son comportement. Exemple : Tu es un assistant pour une boutique de vélos.",
];
@endphp
<div x-data="{
    activeTab: new URLSearchParams(window.location.search).get('tab') || '{{ $groups->keys()->first() }}',
    helpOpen: null,
    helpTexts: @js($helpTextsData)
}">
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-20">
            <iconify-icon icon="solar:check-circle-outline" class="icon text-lg"></iconify-icon>
            {{ session('success') }}
        </div>
    @endif

    @php
        // Principe ADHD: labels traduits et icones coherentes pour chaque onglet
        $icons = [
            'general' => 'solar:settings-outline',
            'mail' => 'solar:letter-outline',
            'seo' => 'solar:chart-outline',
            'sms' => 'solar:phone-outline',
            'branding' => 'solar:palette-outline',
            'security' => 'solar:shield-outline',
            'push' => 'solar:bell-outline',
            'blog' => 'solar:document-text-outline',
            'retention' => 'solar:clock-circle-outline',
            'ai' => 'solar:stars-outline',
        ];
        $labels = [
            'general' => 'Général',
            'mail' => 'Courriel',
            'seo' => 'SEO',
            'sms' => 'SMS',
            'branding' => 'Apparence',
            'security' => 'Sécurité',
            'push' => 'Notifications push',
            'blog' => 'Blog',
            'retention' => 'Rétention',
            'ai' => 'Intelligence artificielle',
        ];
    @endphp

    {{-- Nav Tabs WowDash border-gradient-tab --}}
    <ul class="nav border-gradient-tab nav-pills mb-20 d-inline-flex" role="tablist">
        @foreach($groups as $groupName => $settings)
            @php
                $icon = $icons[$groupName] ?? 'solar:widget-outline';
                $label = $labels[$groupName] ?? ucfirst($groupName);
                $tabId = 'tab-' . Str::slug($groupName);
            @endphp
            <li class="nav-item" role="presentation">
                <button class="nav-link d-flex align-items-center gap-2 px-24"
                        :class="activeTab === '{{ $groupName }}' ? 'active' : ''"
                        type="button" role="tab"
                        @click="activeTab = '{{ $groupName }}'; history.replaceState(null, '', '?tab={{ $groupName }}')"
                        :aria-selected="activeTab === '{{ $groupName }}' ? 'true' : 'false'">
                    <iconify-icon icon="{{ $icon }}" class="text-xl"></iconify-icon>
                    <span class="line-height-1">{{ $label }}</span>
                    <span class="badge bg-neutral-200 text-neutral-600">{{ count($settings) }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content">
        @foreach($groups as $groupName => $settings)
            @php $tabId = 'tab-' . Str::slug($groupName); @endphp
            <div x-show="activeTab === '{{ $groupName }}'" x-transition role="tabpanel">
                <div class="card radius-12">
                    <div class="card-body p-24">

                        {{-- Sélecteur de thème (uniquement dans l'onglet Apparence) --}}
                        @if($groupName === 'branding')
                            @php
                                $themesDir = module_path('Backoffice', 'resources/views/themes');
                                $availableThemes = array_map('basename', array_filter(glob($themesDir . '/*'), 'is_dir'));
                                $currentTheme = \Modules\Settings\Models\Setting::where('key', 'backoffice.theme')->value('value')
                                    ?? config('backoffice.theme', 'wowdash');
                                $themeLabels = [
                                    'wowdash' => 'WowDash',
                                    'tabler'  => 'Tabler',
                                    'backend' => 'Backend',
                                ];
                            @endphp
                            <div class="mb-20 pb-20 border-bottom border-neutral-200">
                                <label class="form-label fw-semibold text-primary-light text-sm mb-12 d-block">
                                    Thème du panneau administration
                                </label>
                                <div class="d-flex gap-16 flex-wrap">
                                    @foreach($availableThemes as $themeName)
                                        @php $isActive = $currentTheme === $themeName; @endphp
                                        <div class="card mb-0 {{ $isActive ? 'border-primary-600' : 'border-neutral-200' }}"
                                             style="width:160px; cursor:pointer; border-width: {{ $isActive ? '2px' : '1px' }};"
                                             wire:click="saveTheme('{{ $themeName }}')">
                                            <div class="card-body text-center py-20 px-12">
                                                <iconify-icon
                                                    icon="solar:palette-outline"
                                                    class="text-2xl {{ $isActive ? 'text-primary-600' : 'text-secondary-light' }}">
                                                </iconify-icon>
                                                <h6 class="mt-8 mb-0 text-sm fw-semibold {{ $isActive ? 'text-primary-600' : 'text-neutral-700' }}">
                                                    {{ $themeLabels[$themeName] ?? ucfirst($themeName) }}
                                                </h6>
                                                @if($isActive)
                                                    <span class="badge bg-primary-600 text-white mt-8 text-xs">Actif</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="d-block text-secondary-light mt-8">
                                    Cliquez sur un thème pour l'appliquer immédiatement. La page sera rechargée.
                                </small>
                            </div>
                        @endif

                        {{-- Principe ADHD: un seul bouton Sauver par onglet au lieu d'un par champ --}}
                        @foreach($settings as $setting)
                            <div class="row mb-20 align-items-center {{ !$loop->last ? 'pb-20 border-bottom border-neutral-200' : '' }}">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="form-label fw-semibold text-primary-light text-sm mb-0">
                                            {{ ucwords(str_replace(['branding.', '_', '.'], ['', ' ', ' '], $setting->key)) }}
                                        </label>
                                        @if(str_starts_with($setting->key, 'ai.'))
                                            <button type="button"
                                                    class="btn btn-sm p-0 border-0 text-neutral-400 bg-hover-neutral-100 rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width: 22px; height: 22px; font-size: 12px; line-height: 1;"
                                                    @click="helpOpen = helpOpen === '{{ $setting->key }}' ? null : '{{ $setting->key }}'"
                                                    :class="helpOpen === '{{ $setting->key }}' ? 'text-primary-600' : ''"
                                                    title="Aide">?</button>
                                        @endif
                                    </div>
                                    @if($setting->description)
                                        <small class="d-block text-secondary-light mt-4">{{ $setting->description }}</small>
                                    @endif
                                    @if(str_starts_with($setting->key, 'ai.'))
                                        <div x-show="helpOpen === '{{ $setting->key }}'" x-transition
                                             class="mt-8 p-12 bg-primary-50 radius-8 text-sm text-neutral-700"
                                             x-text="helpTexts['{{ $setting->key }}'] || ''"></div>
                                    @endif
                                </div>
                                <div class="col-md-8">
                                    @if($setting->type === 'boolean')
                                        <div class="form-check form-switch">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                wire:click="toggleBoolean({{ $setting->id }})"
                                                @checked(filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))
                                            >
                                            <label class="form-check-label text-sm text-secondary-light">
                                                {{ filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'Activé' : 'Désactivé' }}
                                            </label>
                                        </div>
                                    @elseif(str_contains($setting->key, 'color'))
                                        <div class="d-flex gap-2 align-items-center">
                                            <input
                                                type="color"
                                                wire:model="values.{{ $setting->id }}"
                                                class="form-control form-control-color border-0"
                                                style="width:42px;height:38px"
                                            >
                                            <input
                                                type="text"
                                                wire:model="values.{{ $setting->id }}"
                                                class="form-control radius-8"
                                                style="max-width:120px"
                                                placeholder="#000000"
                                            >
                                        </div>
                                    @elseif(str_contains($setting->key, 'description') || str_contains($setting->key, 'subtitle') || str_contains($setting->key, 'footer'))
                                        <textarea
                                            wire:model="values.{{ $setting->id }}"
                                            class="form-control radius-8"
                                            rows="2"
                                            placeholder="{{ $setting->description }}"
                                        ></textarea>
                                    @elseif($setting->type === 'number')
                                        <input
                                            type="number"
                                            wire:model="values.{{ $setting->id }}"
                                            class="form-control radius-8"
                                            style="max-width:120px"
                                            min="1"
                                        >
                                    @elseif(str_contains($setting->key, 'mail_from_address'))
                                        <input
                                            type="email"
                                            wire:model="values.{{ $setting->id }}"
                                            class="form-control radius-8"
                                            placeholder="email@example.com"
                                        >
                                    @elseif(str_contains($setting->key, 'password'))
                                        <input
                                            type="password"
                                            wire:model="values.{{ $setting->id }}"
                                            class="form-control radius-8"
                                            placeholder="••••••••"
                                            autocomplete="new-password"
                                        >
                                    @elseif(str_contains($setting->key, 'mail_port'))
                                        <input
                                            type="number"
                                            wire:model="values.{{ $setting->id }}"
                                            class="form-control radius-8"
                                            style="max-width:120px"
                                            min="1"
                                            max="65535"
                                            placeholder="587"
                                        >
                                    @elseif(str_contains($setting->key, 'font_url') || str_contains($setting->key, 'logo') || str_contains($setting->key, 'favicon'))
                                        <input
                                            type="url"
                                            wire:model="values.{{ $setting->id }}"
                                            class="form-control radius-8"
                                            placeholder="https://..."
                                        >
                                    @elseif($setting->key === 'mail_encryption')
                                        <select wire:model="values.{{ $setting->id }}"
                                                class="form-select radius-8" style="width:160px;">
                                            <option value="tls">TLS (recommandé)</option>
                                            <option value="ssl">SSL</option>
                                            <option value="">Aucun</option>
                                        </select>
                                    @elseif(str_contains($setting->key, '_model') && str_starts_with($setting->key, 'ai.'))
                                        <select
                                            wire:model="values.{{ $setting->id }}"
                                            class="form-select radius-8"
                                        >
                                            @php
                                                $aiModels = [
                                                    'meta-llama/llama-3.3-70b-instruct:free' => 'Llama 3.3 70B (gratuit, polyvalent)',
                                                    'qwen/qwen3-coder:free' => 'Qwen 3 Coder (gratuit, code)',
                                                    'deepseek/deepseek-r1-0528:free' => 'DeepSeek R1 (gratuit, raisonnement)',
                                                    'google/gemma-3-27b-it:free' => 'Gemma 3 27B (gratuit, vision)',
                                                    'arcee-ai/trinity-large-preview:free' => 'Trinity Large (gratuit, fiable)',
                                                    'qwen/qwen3-coder-next' => 'Qwen 3 Coder Next (0.12$/M, excellent)',
                                                    'deepseek/deepseek-v3.2-20251201' => 'DeepSeek V3.2 (0.25$/M, fiable)',
                                                ];
                                            @endphp
                                            @foreach($aiModels as $modelId => $modelLabel)
                                                <option value="{{ $modelId }}">{{ $modelLabel }}</option>
                                            @endforeach
                                            @if(!array_key_exists($setting->value ?? '', $aiModels) && !empty($setting->value))
                                                <option value="{{ $setting->value }}">{{ $setting->value }} (personnalisé)</option>
                                            @endif
                                        </select>
                                    @else
                                        <input
                                            type="text"
                                            wire:model="values.{{ $setting->id }}"
                                            class="form-control radius-8"
                                        >
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        {{-- Principe ADHD: un seul CTA clair en bas de chaque onglet --}}
                        @if($settings->where('type', '!=', 'boolean')->count() > 0)
                            <div class="text-end mt-16 pt-16 border-top border-neutral-200">
                                <button
                                    wire:click="saveGroup('{{ $groupName }}')"
                                    class="btn btn-primary-600 radius-8 d-inline-flex align-items-center gap-2 px-24"
                                >
                                    <iconify-icon icon="solar:check-read-outline" class="text-xl"></iconify-icon>
                                    Sauvegarder
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
