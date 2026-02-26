@php
$labels = [
    'general'   => 'Général',
    'mail'      => 'Courriel',
    'seo'       => 'SEO',
    'sms'       => 'SMS',
    'branding'  => 'Apparence',
    'security'  => 'Sécurité',
    'push'      => 'Notifications push',
    'blog'      => 'Blog',
    'retention' => 'Rétention',
    'ai'        => 'IA',
];
$icons = [
    'general'   => 'ti-settings',
    'mail'      => 'ti-mail',
    'seo'       => 'ti-search',
    'sms'       => 'ti-device-mobile',
    'branding'  => 'ti-palette',
    'security'  => 'ti-shield-lock',
    'push'      => 'ti-bell',
    'blog'      => 'ti-article',
    'retention' => 'ti-clock',
    'ai'        => 'ti-sparkles',
];
@endphp
<div x-data="{
    activeTab: new URLSearchParams(window.location.search).get('tab') || '{{ $groups->keys()->first() }}'
}">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4">
        <div class="d-flex align-items-center">
            <i class="ti ti-check-circle me-2"></i>
            {{ session('success') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <div class="d-flex align-items-center">
            <i class="ti ti-alert-circle me-2"></i>
            {{ session('error') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Onglets --}}
    <ul class="nav nav-tabs mb-4" role="tablist">
        @foreach($groups as $groupName => $settings)
        @php
            $icon  = $icons[$groupName]  ?? 'ti-point';
            $label = $labels[$groupName] ?? ucfirst($groupName);
        @endphp
        <li class="nav-item" role="presentation">
            <button
                type="button"
                role="tab"
                class="nav-link"
                :class="activeTab === '{{ $groupName }}' ? 'active' : ''"
                @click="activeTab = '{{ $groupName }}'; history.replaceState(null,'','?tab={{ $groupName }}')"
                :aria-selected="activeTab === '{{ $groupName }}'">
                <i class="ti {{ $icon }} me-1"></i>
                {{ $label }}
                <span class="badge bg-secondary-lt ms-1">{{ count($settings) }}</span>
            </button>
        </li>
        @endforeach
    </ul>

    {{-- Contenu des onglets --}}
    @foreach($groups as $groupName => $settings)
    @php
        $label = $labels[$groupName] ?? ucfirst($groupName);
    @endphp
    <div x-show="activeTab === '{{ $groupName }}'" x-transition role="tabpanel">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">
                    <i class="ti {{ $icons[$groupName] ?? 'ti-settings' }} me-2 text-muted"></i>
                    Paramètres : {{ $label }}
                </h4>
                @if($settings->where('type', '!=', 'boolean')->count() > 0)
                <button wire:click="saveGroup('{{ $groupName }}')" class="btn btn-primary btn-sm">
                    <i class="ti ti-device-floppy me-1"></i> Sauvegarder
                </button>
                @endif
            </div>
            <div class="card-body p-0">

                {{-- Sélecteur de thème (uniquement dans l'onglet Apparence) --}}
                @if($groupName === 'branding')
                @php
                    $themesDir = module_path('Backoffice', 'resources/views/themes');
                    $availableThemes = array_map('basename', array_filter(glob($themesDir . '/*'), 'is_dir'));
                    $currentTheme = \Modules\Settings\Models\Setting::where('key', 'backoffice.theme')->value('value')
                        ?? config('backoffice.theme', 'wowdash');
                    $themeLabels = [
                        'wowdash'  => 'WowDash',
                        'tabler'   => 'Tabler',
                        'backend'  => 'Backend',
                    ];
                @endphp
                <div class="px-4 pt-4 pb-3 border-bottom">
                    <div class="fw-medium mb-3">Thème du panneau administration</div>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($availableThemes as $themeName)
                        @php $isActive = $currentTheme === $themeName; @endphp
                        <div class="card mb-0 {{ $isActive ? 'border-primary border-2 shadow-sm' : '' }}"
                             style="width:150px; cursor:pointer;"
                             wire:click="saveTheme('{{ $themeName }}')">
                            <div class="card-body text-center py-3 px-2">
                                <i class="ti ti-palette fs-2 {{ $isActive ? 'text-primary' : 'text-muted' }}"></i>
                                <div class="mt-1 fw-medium small {{ $isActive ? 'text-primary' : '' }}">
                                    {{ $themeLabels[$themeName] ?? ucfirst($themeName) }}
                                </div>
                                @if($isActive)
                                <span class="badge bg-primary mt-1">Actif</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="text-muted small mt-2">
                        Cliquez sur un thème pour l'appliquer immédiatement. La page sera rechargée.
                    </div>
                </div>
                @endif

                <div class="list-group list-group-flush">
                    @forelse($settings as $setting)
                    <div class="list-group-item px-4 py-3">
                        <div class="row align-items-center g-3">
                            <div class="col-md-4">
                                <div class="fw-medium">
                                    {{ ucwords(str_replace(['branding.', '_', '.'], ['', ' ', ' '], $setting->key)) }}
                                </div>
                                <small class="text-muted font-monospace">{{ $setting->key }}</small>
                                @if($setting->description)
                                <div class="small text-muted mt-1">{{ $setting->description }}</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                @if($setting->type === 'boolean')
                                <label class="form-check form-switch mb-0">
                                    <input type="checkbox" class="form-check-input"
                                           wire:click="toggleBoolean({{ $setting->id }})"
                                           @checked(filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))>
                                    <span class="form-check-label">
                                        {{ filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'Activé' : 'Désactivé' }}
                                    </span>
                                </label>
                                @elseif(str_contains($setting->key, 'color'))
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color"
                                           wire:model="values.{{ $setting->id }}"
                                           class="form-control form-control-sm p-1"
                                           style="width:50px; height:34px;">
                                    <input type="text"
                                           wire:model="values.{{ $setting->id }}"
                                           class="form-control form-control-sm font-monospace"
                                           style="width:110px;"
                                           placeholder="#000000">
                                </div>
                                @elseif(str_contains($setting->key, 'description') || str_contains($setting->key, 'subtitle') || str_contains($setting->key, 'footer'))
                                <textarea wire:model="values.{{ $setting->id }}"
                                          class="form-control form-control-sm"
                                          rows="2"
                                          placeholder="{{ $setting->description }}"></textarea>
                                @elseif($setting->type === 'number')
                                <input type="number"
                                       wire:model="values.{{ $setting->id }}"
                                       class="form-control form-control-sm"
                                       style="width:100px;"
                                       min="1">
                                @elseif(str_contains($setting->key, 'mail_from_address'))
                                <input type="email"
                                       wire:model="values.{{ $setting->id }}"
                                       class="form-control form-control-sm"
                                       placeholder="email@example.com">
                                @elseif(str_contains($setting->key, 'password'))
                                <input type="password"
                                       wire:model="values.{{ $setting->id }}"
                                       class="form-control form-control-sm"
                                       placeholder="••••••••"
                                       autocomplete="new-password">
                                @elseif(str_contains($setting->key, 'font_url') || str_contains($setting->key, 'logo') || str_contains($setting->key, 'favicon'))
                                <input type="url"
                                       wire:model="values.{{ $setting->id }}"
                                       class="form-control form-control-sm"
                                       placeholder="https://...">
                                @else
                                <input type="text"
                                       wire:model="values.{{ $setting->id }}"
                                       class="form-control form-control-sm">
                                @endif
                            </div>
                            <div class="col-md-2 text-end">
                                <span class="badge bg-secondary-lt text-secondary small">{{ $setting->type ?? 'string' }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">
                        <i class="ti ti-settings-off fs-2 d-block mb-2"></i>
                        Aucun paramètre dans ce groupe
                    </div>
                    @endforelse
                </div>
            </div>
            @if($settings->where('type', '!=', 'boolean')->count() > 5)
            <div class="card-footer text-end">
                <button wire:click="saveGroup('{{ $groupName }}')" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Sauvegarder les modifications
                </button>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
