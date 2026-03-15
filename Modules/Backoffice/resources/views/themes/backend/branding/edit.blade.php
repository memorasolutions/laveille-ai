<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Personnalisation'), 'subtitle' => __('Apparence du site')])

@section('content')

@php
    $colorFields = [
        'primary_color'   => ['label' => __('Primaire'),       'default' => '#6571ff', 'var' => '--bs-primary'],
        'secondary_color' => ['label' => __('Secondaire'),     'default' => '#7987a1', 'var' => '--bs-secondary'],
        'success_color'   => ['label' => __('Succès'),         'default' => '#05a34a', 'var' => '--bs-success'],
        'warning_color'   => ['label' => __('Avertissement'),  'default' => '#fbbc06', 'var' => '--bs-warning'],
        'danger_color'    => ['label' => __('Danger'),         'default' => '#ff3366', 'var' => '--bs-danger'],
        'info_color'      => ['label' => __('Information'),    'default' => '#66d1d1', 'var' => '--bs-info'],
        'sidebar_bg'      => ['label' => __('Fond sidebar'),   'default' => '#0c1427', 'var' => '--sidebar-bg'],
        'header_bg'       => ['label' => __('Fond en-tête'),   'default' => '#ffffff', 'var' => '--header-bg'],
        'body_bg'         => ['label' => __('Fond page'),      'default' => '#ffffff', 'var' => '--bs-body-bg'],
    ];

    $fontList = ['Inter', 'Roboto', 'Open Sans', 'Poppins', 'Nunito', 'Lato', 'Montserrat', 'Source Sans 3', 'DM Sans', 'Plus Jakarta Sans', 'Raleway', 'Work Sans', 'Outfit', 'Manrope', 'Figtree'];
    $currentFont = old('font_family', $settings['font_family'] ?? 'Inter');

    $logoFields = [
        'logo_light' => ['label' => __('Logo (mode clair)'), 'hint' => __('PNG ou SVG, max 2 Mo')],
        'logo_dark'  => ['label' => __('Logo (mode sombre)'), 'hint' => __('PNG ou SVG, max 2 Mo')],
        'logo_icon'  => ['label' => __('Logo icône'), 'hint' => __('Carré, max 1 Mo')],
        'favicon'    => ['label' => __('Favicon'), 'hint' => __('PNG 16x16 ou 32x32, max 512 Ko')],
    ];
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="paintbrush" class="icon-md text-primary"></i>{{ __('Identité visuelle') }}</h4>
    <x-backoffice::help-modal id="helpBrandingModal" :title="__('Identité visuelle')" icon="paintbrush" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.branding._help')
    </x-backoffice::help-modal>
</div>

<form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row g-4">

        {{-- ===== COLONNE GAUCHE : Onglets ===== --}}
        <div class="col-xl-8">
            <ul class="nav nav-tabs mb-4" id="brandingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-identity-tab" data-bs-toggle="tab"
                            data-bs-target="#tab-identity" type="button" role="tab"
                            aria-controls="tab-identity" aria-selected="true">
                        <i data-lucide="building-2" class="icon-sm me-1"></i>
                        {{ __('Identité') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-colors-tab" data-bs-toggle="tab"
                            data-bs-target="#tab-colors" type="button" role="tab"
                            aria-controls="tab-colors" aria-selected="false">
                        <i data-lucide="palette" class="icon-sm me-1"></i>
                        {{ __('Couleurs') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-typography-tab" data-bs-toggle="tab"
                            data-bs-target="#tab-typography" type="button" role="tab"
                            aria-controls="tab-typography" aria-selected="false">
                        <i data-lucide="type" class="icon-sm me-1"></i>
                        {{ __('Typographie') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-logos-tab" data-bs-toggle="tab"
                            data-bs-target="#tab-logos" type="button" role="tab"
                            aria-controls="tab-logos" aria-selected="false">
                        <i data-lucide="image" class="icon-sm me-1"></i>
                        {{ __('Logos') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-footer-tab" data-bs-toggle="tab"
                            data-bs-target="#tab-footer" type="button" role="tab"
                            aria-controls="tab-footer" aria-selected="false">
                        <i data-lucide="panel-bottom" class="icon-sm me-1"></i>
                        {{ __('Pied de page') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="brandingTabsContent">

                {{-- ===== ONGLET 1 : Identité ===== --}}
                <div class="tab-pane fade show active" id="tab-identity" role="tabpanel"
                     aria-labelledby="tab-identity-tab" tabindex="0">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label for="site_name" class="form-label fw-semibold">
                                    {{ __('Nom du site') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="site_name" id="site_name"
                                       class="form-control @error('site_name') is-invalid @enderror"
                                       value="{{ old('site_name', $settings['site_name'] ?? '') }}" required>
                                @error('site_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <x-editor::tiptap name="site_description" :value="old('site_description', $settings['site_description'] ?? '')" :label="__('Description')" />
                            </div>
                            <div class="mb-3">
                                <label for="login_title" class="form-label fw-semibold">{{ __('Titre page de connexion') }}</label>
                                <input type="text" name="login_title" id="login_title"
                                       class="form-control"
                                       value="{{ old('login_title', $settings['login_title'] ?? __('Connexion')) }}">
                            </div>
                            <div>
                                <x-editor::tiptap name="login_subtitle" :value="old('login_subtitle', $settings['login_subtitle'] ?? '')" :label="__('Sous-titre page de connexion')" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== ONGLET 2 : Couleurs ===== --}}
                <div class="tab-pane fade" id="tab-colors" role="tabpanel"
                     aria-labelledby="tab-colors-tab" tabindex="0">
                    <div class="card">
                        <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-semibold">{{ __('Palette de couleurs') }}</h5>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="resetColors">
                                <i data-lucide="rotate-ccw" style="width:14px;height:14px;" class="me-1"></i>
                                {{ __('Réinitialiser') }}
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                @foreach($colorFields as $key => $info)
                                    <div class="col-sm-4">
                                        <label for="{{ $key }}" class="form-label fw-semibold small mb-1">{{ $info['label'] }}</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" name="{{ $key }}" id="{{ $key }}"
                                                   class="form-control form-control-color rounded border"
                                                   data-css-var="{{ $info['var'] }}"
                                                   data-default="{{ $info['default'] }}"
                                                   style="width:44px;height:38px;"
                                                   value="{{ old($key, $settings[$key] ?? $info['default']) }}">
                                            <input type="text" id="{{ $key }}_hex"
                                                   class="form-control form-control-sm font-monospace"
                                                   style="width:90px;font-size:12px;"
                                                   aria-label="{{ __('Code hexadécimal') }} {{ $info['label'] }}"
                                                   value="{{ old($key, $settings[$key] ?? $info['default']) }}" readonly>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== ONGLET 3 : Typographie ===== --}}
                <div class="tab-pane fade" id="tab-typography" role="tabpanel"
                     aria-labelledby="tab-typography-tab" tabindex="0">
                    <div class="d-flex flex-column gap-4">

                        {{-- Police du corps --}}
                        <div class="card">
                            <div class="card-header py-3 px-4 border-bottom">
                                <h5 class="card-title mb-0 fw-semibold">{{ __('Police du corps') }}</h5>
                            </div>
                            <div class="card-body p-4" x-data="fontPicker('{{ $currentFont }}')">
                                <input type="hidden" name="font_family" :value="selectedFont">
                                <input type="hidden" name="font_url" id="font_url" value="{{ old('font_url', $settings['font_url'] ?? '') }}">

                                <label id="label-font" class="form-label fw-semibold">{{ __('Police') }} <span class="text-danger">*</span></label>
                                <div class="position-relative" @keydown.escape="open = false" @keydown.arrow-down.prevent="open = true" @keydown.arrow-up.prevent="open = true">
                                    <button type="button" class="form-select text-start"
                                            @click="open = !open"
                                            :aria-expanded="open"
                                            aria-haspopup="listbox"
                                            aria-labelledby="label-font"
                                            x-text="selectedFont"></button>
                                    <div x-show="open" x-cloak @click.outside="open = false"
                                         role="listbox" aria-labelledby="label-font"
                                         class="position-absolute w-100 bg-body border rounded-3 shadow-sm mt-1 overflow-auto"
                                         style="max-height:300px;z-index:10;">
                                        @foreach($fontList as $font)
                                            <button type="button" role="option"
                                                    :aria-selected="selectedFont === '{{ $font }}'"
                                                    class="dropdown-item px-3 py-2 d-flex justify-content-between align-items-center"
                                                    style="font-family:'{{ $font }}',sans-serif;"
                                                    :class="{ 'bg-primary text-white': selectedFont === '{{ $font }}' }"
                                                    @click="selectFont('{{ $font }}')">
                                                {{ $font }}
                                                <small class="opacity-75" style="font-family:'{{ $font }}',sans-serif;">Aa</small>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-3 p-3 border rounded-3 bg-body-secondary">
                                    <p class="mb-1 small text-muted">{{ __('Aperçu') }}</p>
                                    <p class="mb-0 fs-5" :style="{ fontFamily: selectedFont + ', sans-serif' }" x-text="'{{ __('Aperçu de la police') }} ' + selectedFont">
                                    </p>
                                    <p class="mb-0 mt-1" :style="{ fontFamily: selectedFont + ', sans-serif' }">
                                        ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz 0123456789
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Typographie du titre --}}
                        <div class="card">
                            <div class="card-header py-3 px-4 border-bottom">
                                <h5 class="card-title mb-0 fw-semibold">{{ __('Typographie du titre') }}</h5>
                            </div>
                            <div class="card-body p-4">
                                <div x-data="{
                                    fontFamily: '{{ old('topbar_font_family', $settings['topbar_font_family'] ?? 'Roboto') }}',
                                    fontSize: {{ old('topbar_font_size', isset($settings['topbar_font_size']) ? floatval(str_replace('rem', '', $settings['topbar_font_size'])) : 1.25) }},
                                    fontWeight: '{{ old('topbar_font_weight', $settings['topbar_font_weight'] ?? '700') }}',
                                    letterSpacing: {{ old('topbar_letter_spacing', isset($settings['topbar_letter_spacing']) ? floatval(str_replace('px', '', $settings['topbar_letter_spacing'])) : 0) }},
                                    wordSpacing: {{ old('topbar_word_spacing', isset($settings['topbar_word_spacing']) ? floatval(str_replace('px', '', $settings['topbar_word_spacing'])) : 0) }},
                                    textTransform: '{{ old('topbar_text_transform', $settings['topbar_text_transform'] ?? 'none') }}',
                                    init() { this.updateCSSVariables(); },
                                    updateCSSVariables() {
                                        document.documentElement.style.setProperty('--topbar-font-family', this.fontFamily + ', sans-serif');
                                        document.documentElement.style.setProperty('--topbar-font-size', this.fontSize + 'rem');
                                        document.documentElement.style.setProperty('--topbar-font-weight', this.fontWeight);
                                        document.documentElement.style.setProperty('--topbar-letter-spacing', this.letterSpacing + 'px');
                                        document.documentElement.style.setProperty('--topbar-word-spacing', this.wordSpacing + 'px');
                                        document.documentElement.style.setProperty('--topbar-text-transform', this.textTransform);
                                    }
                                }" x-effect="updateCSSVariables()">

                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <label class="form-label fw-medium">{{ __('Famille de police') }}</label>
                                            <select x-model="fontFamily" name="topbar_font_family" class="form-select">
                                                @php
                                                    $topbarFonts = ['Roboto', 'Inter', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Nunito', 'Ubuntu', 'Playfair Display', 'Merriweather', 'Source Sans 3', 'Work Sans', 'DM Sans', 'Fira Sans'];
                                                    $currentTopbarFont = old('topbar_font_family', $settings['topbar_font_family'] ?? 'Roboto');
                                                @endphp
                                                @foreach($topbarFonts as $font)
                                                    <option value="{{ $font }}" {{ $currentTopbarFont == $font ? 'selected' : '' }}
                                                            style="font-family:'{{ $font }}',sans-serif;">{{ $font }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fw-medium">{{ __('Taille') }} <span class="text-muted" x-text="parseFloat(fontSize).toFixed(2) + ' rem'"></span></label>
                                            <input type="range" x-model="fontSize" min="0.875" max="2.5" step="0.125" class="form-range">
                                            <input type="hidden" name="topbar_font_size" x-bind:value="fontSize + 'rem'">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fw-medium">{{ __('Graisse') }}</label>
                                            <select x-model="fontWeight" name="topbar_font_weight" class="form-select">
                                                @php
                                                    $weights = ['300' => 'Light (300)', '400' => 'Normal (400)', '500' => 'Medium (500)', '700' => 'Bold (700)', '900' => 'Black (900)'];
                                                    $currentWeight = old('topbar_font_weight', $settings['topbar_font_weight'] ?? '700');
                                                @endphp
                                                @foreach($weights as $val => $label)
                                                    <option value="{{ $val }}" {{ $currentWeight == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fw-medium">{{ __('Espacement lettres') }} <span class="text-muted" x-text="letterSpacing + ' px'"></span></label>
                                            <input type="range" x-model="letterSpacing" min="-1" max="5" step="0.5" class="form-range">
                                            <input type="hidden" name="topbar_letter_spacing" x-bind:value="letterSpacing + 'px'">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fw-medium">{{ __('Espacement mots') }} <span class="text-muted" x-text="wordSpacing + ' px'"></span></label>
                                            <input type="range" x-model="wordSpacing" min="0" max="10" step="0.5" class="form-range">
                                            <input type="hidden" name="topbar_word_spacing" x-bind:value="wordSpacing + 'px'">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fw-medium">{{ __('Transformation') }}</label>
                                            <select x-model="textTransform" name="topbar_text_transform" class="form-select">
                                                @php
                                                    $transforms = ['none' => __('Aucune'), 'uppercase' => __('MAJUSCULES'), 'capitalize' => __('Première lettre')];
                                                    $currentTransform = old('topbar_text_transform', $settings['topbar_text_transform'] ?? 'none');
                                                @endphp
                                                @foreach($transforms as $val => $label)
                                                    <option value="{{ $val }}" {{ $currentTransform == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-3 border-top">
                                        <p class="small text-muted mb-2">{{ __('Aperçu en temps réel') }}</p>
                                        <div class="p-3 border rounded-3 bg-body-secondary" x-bind:style="{
                                            fontFamily: fontFamily + ', sans-serif',
                                            fontSize: fontSize + 'rem',
                                            fontWeight: fontWeight,
                                            letterSpacing: letterSpacing + 'px',
                                            wordSpacing: wordSpacing + 'px',
                                            textTransform: textTransform
                                        }">
                                            {{ $settings['site_name'] ?? config('app.name') }}
                                        </div>
                                        <p class="small text-muted mt-1 mb-0">{{ __('Le titre dans la sidebar change aussi en temps réel.') }}</p>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ===== ONGLET 4 : Logos ===== --}}
                <div class="tab-pane fade" id="tab-logos" role="tabpanel"
                     aria-labelledby="tab-logos-tab" tabindex="0">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="row g-4">
                                @foreach($logoFields as $field => $info)
                                    <div class="col-sm-6" x-data="{ hasFile: {{ !empty($settings[$field] ?? '') ? 'true' : 'false' }} }">
                                        <label class="form-label fw-semibold">{{ $info['label'] }}</label>
                                        <div
                                            class="position-relative d-flex flex-column align-items-center justify-content-center text-center rounded-3 border border-2 border-dashed"
                                            style="height:180px; cursor:pointer; border-color:var(--bs-border-color); transition:border-color .15s;"
                                            id="dropzone_{{ $field }}"
                                            role="button" tabindex="0"
                                            aria-label="{{ $info['label'] }} - {{ $info['hint'] }}"
                                            @keydown.enter="document.getElementById('input_{{ $field }}').click()"
                                            @keydown.space.prevent="document.getElementById('input_{{ $field }}').click()"
                                            @mouseenter="$el.style.borderColor='var(--bs-primary, #6571ff)'"
                                            @mouseleave="$el.style.borderColor=''"
                                            @click="document.getElementById('input_{{ $field }}').click()"
                                            @dragover.prevent
                                            @drop.prevent="
                                                const files = $event.dataTransfer.files;
                                                if (files.length > 0 && files[0].type.startsWith('image/')) {
                                                    document.getElementById('input_{{ $field }}').files = files;
                                                    const reader = new FileReader();
                                                    reader.onload = e => { document.getElementById('img_{{ $field }}').src = e.target.result; hasFile = true; };
                                                    reader.readAsDataURL(files[0]);
                                                }
                                            ">

                                            {{-- Aperçu existant --}}
                                            <div x-show="hasFile" x-cloak class="position-absolute top-0 start-0 end-0 bottom-0 align-items-center justify-content-center p-3" style="display:flex;">
                                                <img id="img_{{ $field }}"
                                                     src="{{ !empty($settings[$field] ?? '') ? asset('storage/' . $settings[$field]) : '' }}"
                                                     alt="{{ $info['label'] }}"
                                                     style="max-height:100%;max-width:100%;object-fit:contain;">
                                                <button type="button"
                                                        @click.stop="document.getElementById('input_{{ $field }}').value = ''; hasFile = false;"
                                                        class="position-absolute top-0 end-0 m-2 btn btn-sm btn-outline-danger rounded-circle d-flex align-items-center justify-content-center p-0"
                                                        style="width:22px;height:22px;font-size:10px;line-height:1;"
                                                        title="{{ __('Supprimer') }}" aria-label="{{ __('Supprimer') }} {{ $info['label'] }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" style="width:10px;height:10px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            {{-- Placeholder --}}
                                            <div x-show="!hasFile" class="d-flex flex-column align-items-center px-3">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                     style="width:48px;height:48px;color:#adb5bd;margin-bottom:8px;flex-shrink:0;"
                                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                <p class="small text-muted mb-1">{{ __('Glisser-déposer ou cliquer') }}</p>
                                                <span class="small text-muted opacity-75">{{ $info['hint'] }}</span>
                                            </div>

                                            <input type="file" name="{{ $field }}" id="input_{{ $field }}"
                                                   accept="image/*" class="d-none"
                                                   @change="
                                                       if ($event.target.files.length > 0) {
                                                           const reader = new FileReader();
                                                           reader.onload = e => { document.getElementById('img_{{ $field }}').src = e.target.result; hasFile = true; };
                                                           reader.readAsDataURL($event.target.files[0]);
                                                       }
                                                   ">
                                        </div>
                                        @error($field)
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== ONGLET 5 : Pied de page ===== --}}
                <div class="tab-pane fade" id="tab-footer" role="tabpanel"
                     aria-labelledby="tab-footer-tab" tabindex="0">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label for="footer_text" class="form-label fw-semibold">{{ __('Texte footer gauche') }}</label>
                                <input type="text" name="footer_text" id="footer_text"
                                       class="form-control"
                                       value="{{ old('footer_text', $settings['footer_text'] ?? '') }}"
                                       placeholder="&copy; {year} {app_name}. {{ __('Tous droits réservés.') }}">
                                <div class="form-text text-muted">{{ __('Variables') }} : <code>{year}</code>, <code>{app_name}</code></div>
                            </div>
                            <div>
                                <label for="footer_right" class="form-label fw-semibold">{{ __('Texte footer droit') }}</label>
                                <input type="text" name="footer_right" id="footer_right"
                                       class="form-control"
                                       value="{{ old('footer_right', $settings['footer_right'] ?? '') }}"
                                       placeholder="Laravel v{version} - PHP v{php_version}">
                                <div class="form-text text-muted">{{ __('Variables') }} : <code>{version}</code>, <code>{php_version}</code></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ===== COLONNE DROITE : Aperçu sticky ===== --}}
        <div class="col-xl-4">
            <div class="position-sticky" style="top:1rem;">
                <div class="card">
                    <div class="card-header py-3 px-4 border-bottom">
                        <h5 class="card-title mb-0 fw-semibold">{{ __('Aperçu global') }}</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="border rounded-3 overflow-hidden" style="height:220px;" aria-hidden="true">
                            <div class="d-flex h-100">
                                {{-- Mini sidebar --}}
                                <div id="mockup-sidebar" class="d-flex flex-column align-items-center pt-3"
                                     style="width:60px;background:var(--sidebar-bg, #0c1427);flex-shrink:0;">
                                    <div class="rounded-circle bg-white bg-opacity-25 mb-2" style="width:24px;height:24px;"></div>
                                    <div class="rounded bg-white bg-opacity-10 mb-1" style="width:36px;height:6px;"></div>
                                    <div class="rounded bg-white bg-opacity-10 mb-1" style="width:36px;height:6px;"></div>
                                    <div class="rounded bg-white bg-opacity-10 mb-1" style="width:36px;height:6px;"></div>
                                </div>
                                <div class="flex-grow-1 d-flex flex-column">
                                    {{-- Mini header --}}
                                    <div id="mockup-header" class="border-bottom px-3 d-flex align-items-center"
                                         style="height:36px;background:var(--header-bg, #ffffff);">
                                        <div class="rounded bg-secondary bg-opacity-25" style="width:80px;height:8px;"></div>
                                        <div class="ms-auto rounded-circle bg-secondary bg-opacity-25" style="width:20px;height:20px;"></div>
                                    </div>
                                    {{-- Mini body --}}
                                    <div id="mockup-body" class="flex-grow-1 p-3" style="background:var(--bs-body-bg, #ffffff);">
                                        <div id="preview-site-name" class="fw-semibold small mb-2">{{ $settings['site_name'] ?? config('app.name') }}</div>
                                        <div class="d-flex gap-1 flex-wrap mb-2">
                                            <span class="badge" style="background:var(--bs-primary, #6571ff);">{{ __('Primaire') }}</span>
                                            <span class="badge" style="background:var(--bs-secondary, #7987a1);">{{ __('Secondaire') }}</span>
                                            <span class="badge" style="background:var(--bs-success, #05a34a);">{{ __('Succès') }}</span>
                                            <span class="badge text-dark" style="background:var(--bs-warning, #fbbc06);">{{ __('Avert.') }}</span>
                                            <span class="badge" style="background:var(--bs-danger, #ff3366);">{{ __('Danger') }}</span>
                                            <span class="badge" style="background:var(--bs-info, #66d1d1);">Info</span>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-primary" style="font-size:11px;">{{ __('Bouton') }}</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" style="font-size:11px;">{{ __('Annuler') }}</button>
                                        </div>
                                        <p id="mockup-font" class="small text-muted mt-2 mb-0">{{ __('Police') }} : {{ $currentFont }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Boutons d'action --}}
                <div class="d-flex align-items-center gap-3 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" class="icon-sm me-1"></i>
                        {{ __('Enregistrer') }}
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-danger">{{ __('Annuler') }}</a>
                </div>
            </div>
        </div>

    </div>
</form>

@endsection

@push('styles')
<style>
    /* Google Fonts CDN — acceptable ici (page admin authentifiée, preview only).
       Le site public utilise @fontsource (self-hosted) pour la police active. */
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Open+Sans:wght@400;500&family=Poppins:wght@400;500&family=Nunito:wght@400;500&family=Lato:wght@400;700&family=Montserrat:wght@400;500&family=Source+Sans+3:wght@400;500&family=DM+Sans:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500&family=Raleway:wght@400;500&family=Work+Sans:wght@400;500&family=Outfit:wght@400;500&family=Manrope:wght@400;500&family=Figtree:wght@400;500&display=swap');
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ===== Color pickers : preview temps réel =====
    const colorInputs = document.querySelectorAll('input[type="color"][data-css-var]');
    colorInputs.forEach(function (input) {
        const hexDisplay = document.getElementById(input.id + '_hex');
        input.addEventListener('input', function () {
            hexDisplay.value = this.value;
            document.documentElement.style.setProperty(input.dataset.cssVar, this.value);
            if (input.dataset.cssVar === '--bs-body-bg') {
                document.documentElement.style.setProperty('--bs-app-bg', this.value);
            }
            const r = parseInt(this.value.substr(1, 2), 16);
            const g = parseInt(this.value.substr(3, 2), 16);
            const b = parseInt(this.value.substr(5, 2), 16);
            document.documentElement.style.setProperty(input.dataset.cssVar + '-rgb', r + ',' + g + ',' + b);
        });
    });

    // ===== Reset couleurs =====
    document.getElementById('resetColors').addEventListener('click', function () {
        colorInputs.forEach(function (input) {
            input.value = input.dataset.default;
            input.dispatchEvent(new Event('input'));
        });
    });

    // ===== Nom du site → aperçu =====
    const siteNameInput = document.getElementById('site_name');
    const previewSiteName = document.getElementById('preview-site-name');
    if (siteNameInput && previewSiteName) {
        siteNameInput.addEventListener('input', function () {
            previewSiteName.textContent = this.value;
        });
    }
});

// ===== Font picker Alpine component =====
document.addEventListener('alpine:init', function () {
    Alpine.data('fontPicker', function (initial) {
        return {
            open: false,
            selectedFont: initial,
            selectFont(name) {
                this.selectedFont = name;
                this.open = false;
                document.body.style.fontFamily = "'" + name + "', sans-serif";
                const mockupFont = document.getElementById('mockup-font');
                if (mockupFont) mockupFont.textContent = @json(__('Police')) + ' : ' + name;
            }
        };
    });
});
</script>
@endpush
