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
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
            <i data-lucide="check-circle" class="icon-sm"></i>
            {{ session('success') }}
        </div>
    @endif

    @php
        $icons = [
            'general'   => 'settings',
            'homepage'  => 'home',
            'mail'      => 'mail',
            'seo'       => 'bar-chart-2',
            'sms'       => 'smartphone',
            'branding'  => 'palette',
            'security'  => 'shield-check',
            'push'      => 'bell',
            'blog'      => 'file-text',
            'retention' => 'clock',
            'ai'        => 'sparkles',
        ];
        $labels = [
            'general'   => 'Général',
            'homepage'  => 'Accueil',
            'mail'      => 'Courriel',
            'seo'       => 'SEO',
            'sms'       => 'SMS',
            'branding'  => 'Apparence',
            'security'  => 'Sécurité',
            'push'      => 'Notifications push',
            'blog'      => 'Blog',
            'retention' => 'Rétention',
            'ai'        => 'Intelligence artificielle',
        ];
    @endphp

    {{-- Tabs navigation --}}
    <div class="d-flex flex-wrap gap-2 mb-4 border-bottom pb-3">
        @foreach($groups as $groupName => $settings)
            @php
                $icon  = $icons[$groupName] ?? 'layout-grid';
                $label = $labels[$groupName] ?? ucfirst($groupName);
            @endphp
            <button
                type="button"
                class="btn btn-sm d-inline-flex align-items-center gap-2"
                :class="activeTab === '{{ $groupName }}'
                    ? 'btn-primary fw-medium'
                    : 'btn-light text-muted'"
                @click="activeTab = '{{ $groupName }}'; history.replaceState(null, '', '?tab={{ $groupName }}')"
                :aria-selected="activeTab === '{{ $groupName }}'"
                role="tab"
            >
                <i data-lucide="{{ $icon }}" class="icon-sm"></i>
                <span>{{ $label }}</span>
                <span class="badge rounded-pill"
                      :class="activeTab === '{{ $groupName }}' ? 'bg-white text-primary' : 'bg-secondary bg-opacity-25 text-muted'">
                    {{ count($settings) }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- Tab content --}}
    <div>
        @foreach($groups as $groupName => $settings)
            <div x-show="activeTab === '{{ $groupName }}'" x-transition role="tabpanel">
                <div class="card border">
                    <div class="card-body p-4">

                        {{-- Sélecteur de page d'accueil (uniquement dans l'onglet Accueil) --}}
                        @if($groupName === 'homepage')
                            @php
                                $homepageTypeSetting = $settings->firstWhere('key', 'homepage.type');
                                $homepagePageSetting = $settings->firstWhere('key', 'homepage.page_id');
                                $publishedPages = class_exists(\Modules\Pages\Models\StaticPage::class)
                                    ? \Modules\Pages\Models\StaticPage::where('status', 'published')->get(['id', 'title'])
                                    : collect();
                            @endphp
                            <div class="mb-4 pb-4 border-bottom">
                                <label class="fw-semibold text-dark mb-3 d-block">
                                    <i data-lucide="home" class="icon-sm me-1"></i> Page d'accueil du site
                                </label>
                                <p class="small text-muted mb-3">Choisissez ce qui s'affiche quand un visiteur arrive sur votre site.</p>

                                @if($homepageTypeSetting)
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small">Type d'accueil</label>
                                        <select wire:model="values.{{ $homepageTypeSetting->id }}"
                                                class="form-select"
                                                aria-label="Type de page d'accueil">
                                            <option value="landing">Landing page (par défaut)</option>
                                            <option value="page">Page statique</option>
                                        </select>
                                    </div>
                                    @if($homepagePageSetting)
                                    <div class="col-md-6">
                                        <label class="form-label small">Page statique</label>
                                        <select wire:model="values.{{ $homepagePageSetting->id }}"
                                                class="form-select"
                                                aria-label="Page statique comme accueil">
                                            <option value="">-- Sélectionner une page --</option>
                                            @foreach($publishedPages as $pg)
                                                <option value="{{ $pg->id }}">{{ $pg->title }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Visible uniquement si le type est "Page statique".</small>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                    <button wire:click="saveGroup('homepage')"
                                            class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                                        <i data-lucide="check" class="icon-sm"></i>
                                        Sauvegarder
                                    </button>
                                </div>
                            </div>
                            @php
                                $settings = $settings->reject(fn($s) => in_array($s->key, ['homepage.type', 'homepage.page_id']));
                            @endphp
                        @endif

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
                            <div class="mb-4 pb-4 border-bottom">
                                <label class="fw-semibold text-dark mb-3 d-block">
                                    Thème du panneau administration
                                </label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach($availableThemes as $themeName)
                                        @php $isActive = $currentTheme === $themeName; @endphp
                                        <div class="border rounded-3 {{ $isActive ? 'border-primary border-2 shadow-sm' : '' }}"
                                             style="width:160px; cursor:pointer;"
                                             role="button"
                                             wire:click="saveTheme('{{ $themeName }}')">
                                            <div class="text-center py-4 px-3">
                                                <i data-lucide="palette" class="{{ $isActive ? 'text-primary' : 'text-muted' }} icon-md"></i>
                                                <p class="mt-2 mb-0 fw-semibold {{ $isActive ? 'text-primary' : 'text-dark' }}">
                                                    {{ $themeLabels[$themeName] ?? ucfirst($themeName) }}
                                                </p>
                                                @if($isActive)
                                                    <span class="badge bg-primary rounded-pill mt-2 d-inline-block">Actif</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="small text-muted mt-2 mb-0">
                                    Cliquez sur un thème pour l'appliquer immédiatement. La page sera rechargée.
                                </p>
                            </div>
                        @endif

                        @foreach($settings as $setting)
                            <div class="row g-3 align-items-start {{ !$loop->last ? 'pb-4 mb-4 border-bottom' : '' }}">
                                <div class="col-md-5">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="fw-semibold text-dark mb-0">
                                            {{ ucwords(str_replace(['branding.', '_', '.'], ['', ' ', ' '], $setting->key)) }}
                                        </label>
                                        @if(str_starts_with($setting->key, 'ai.'))
                                            <button type="button"
                                                    class="btn btn-sm btn-light rounded-circle d-inline-flex align-items-center justify-content-center fw-bold p-0"
                                                    style="width:20px;height:20px;font-size:11px;"
                                                    @click="helpOpen = helpOpen === '{{ $setting->key }}' ? null : '{{ $setting->key }}'"
                                                    :class="helpOpen === '{{ $setting->key }}' ? 'text-primary' : 'text-muted'"
                                                    title="Aide">?</button>
                                        @endif
                                    </div>
                                    @if($setting->description)
                                        <small class="d-block text-muted mt-1">{{ $setting->description }}</small>
                                    @endif
                                    @if(str_starts_with($setting->key, 'ai.'))
                                        <div x-show="helpOpen === '{{ $setting->key }}'" x-transition
                                             class="mt-2 p-3 bg-primary bg-opacity-10 rounded small text-muted lh-base"
                                             x-text="helpTexts['{{ $setting->key }}'] || ''"></div>
                                    @endif
                                </div>
                                <div class="col-md-7">
                                    @if($setting->type === 'boolean')
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       wire:click="toggleBoolean({{ $setting->id }})"
                                                       @checked(filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))
                                                       aria-label="{{ ucwords(str_replace(['branding.', '_', '.'], ['', ' ', ' '], $setting->key)) }}">
                                            </div>
                                            <span class="text-muted">
                                                {{ filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'Activé' : 'Désactivé' }}
                                            </span>
                                        </div>
                                    @elseif(str_contains($setting->key, 'color'))
                                        <div class="d-flex gap-2 align-items-center">
                                            <input type="color"
                                                   wire:model="values.{{ $setting->id }}"
                                                   class="form-control form-control-color"
                                                   style="width:40px;height:38px;"
                                                   aria-label="Couleur {{ ucwords(str_replace(['branding.', '_', '.'], ['', ' ', ' '], $setting->key)) }}">
                                            <input type="text"
                                                   wire:model="values.{{ $setting->id }}"
                                                   class="form-control"
                                                   style="width:130px;"
                                                   placeholder="#000000"
                                                   aria-label="Valeur hexadécimale {{ ucwords(str_replace(['branding.', '_', '.'], ['', ' ', ' '], $setting->key)) }}">
                                        </div>
                                    @elseif(str_contains($setting->key, 'description') || str_contains($setting->key, 'subtitle') || str_contains($setting->key, 'footer'))
                                        <textarea wire:model="values.{{ $setting->id }}"
                                                  class="form-control"
                                                  rows="2"
                                                  placeholder="{{ $setting->description }}"
                                                  aria-label="{{ ucwords(str_replace(['branding.', '_', '.'], ['', ' ', ' '], $setting->key)) }}"></textarea>
                                    @elseif($setting->type === 'number')
                                        <input type="number"
                                               wire:model="values.{{ $setting->id }}"
                                               class="form-control"
                                               style="width:120px;"
                                               min="1"
                                               aria-label="{{ ucwords(str_replace(['branding.', '_', '.'], ['', ' ', ' '], $setting->key)) }}">
                                    @elseif(str_contains($setting->key, 'mail_from_address'))
                                        <input type="email"
                                               wire:model="values.{{ $setting->id }}"
                                               class="form-control w-100"
                                               placeholder="email@example.com"
                                               aria-label="Adresse email expéditeur">
                                    @elseif(str_contains($setting->key, 'password'))
                                        <input type="password"
                                               wire:model="values.{{ $setting->id }}"
                                               class="form-control w-100"
                                               placeholder="••••••••"
                                               autocomplete="new-password"
                                               aria-label="{{ ucwords(str_replace(['branding.', '_', '.'], ['', ' ', ' '], $setting->key)) }}">
                                    @elseif(str_contains($setting->key, 'mail_port'))
                                        <input type="number"
                                               wire:model="values.{{ $setting->id }}"
                                               class="form-control"
                                               style="width:120px;"
                                               min="1" max="65535" placeholder="587"
                                               aria-label="Port serveur mail">
                                    @elseif(str_contains($setting->key, 'font_url') || str_contains($setting->key, 'logo') || str_contains($setting->key, 'favicon'))
                                        <input type="url"
                                               wire:model="values.{{ $setting->id }}"
                                               class="form-control w-100"
                                               placeholder="https://..."
                                               aria-label="{{ ucwords(str_replace(['branding.', '_', '.'], ['', ' ', ' '], $setting->key)) }}">
                                    @elseif($setting->key === 'mail_encryption')
                                        <select wire:model="values.{{ $setting->id }}"
                                                class="form-select" style="width:160px;"
                                                aria-label="Chiffrement mail">
                                            <option value="tls">TLS (recommandé)</option>
                                            <option value="ssl">SSL</option>
                                            <option value="">Aucun</option>
                                        </select>
                                    @elseif(str_contains($setting->key, '_model') && str_starts_with($setting->key, 'ai.'))
                                        <select wire:model="values.{{ $setting->id }}"
                                                class="form-select w-100"
                                                aria-label="Modèle IA {{ ucwords(str_replace(['ai.', '_'], ['', ' '], $setting->key)) }}">
                                            @php
                                                $aiModels = [
                                                    'meta-llama/llama-3.3-70b-instruct:free' => 'Llama 3.3 70B (gratuit, polyvalent)',
                                                    'qwen/qwen3-coder:free'                  => 'Qwen 3 Coder (gratuit, code)',
                                                    'deepseek/deepseek-r1-0528:free'         => 'DeepSeek R1 (gratuit, raisonnement)',
                                                    'google/gemma-3-27b-it:free'             => 'Gemma 3 27B (gratuit, vision)',
                                                    'arcee-ai/trinity-large-preview:free'    => 'Trinity Large (gratuit, fiable)',
                                                    'qwen/qwen3-coder-next'                  => 'Qwen 3 Coder Next (0.12$/M, excellent)',
                                                    'deepseek/deepseek-v3.2-20251201'        => 'DeepSeek V3.2 (0.25$/M, fiable)',
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
                                        <input type="text"
                                               wire:model="values.{{ $setting->id }}"
                                               class="form-control w-100"
                                               aria-label="{{ ucwords(str_replace(['branding.', '_', '.'], ['', ' ', ' '], $setting->key)) }}">
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        {{-- Save button per tab --}}
                        @if($settings->where('type', '!=', 'boolean')->count() > 0)
                            <div class="d-flex justify-content-end mt-4 pt-4 border-top">
                                <button wire:click="saveGroup('{{ $groupName }}')"
                                        class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                                    <i data-lucide="check" class="icon-sm"></i>
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
