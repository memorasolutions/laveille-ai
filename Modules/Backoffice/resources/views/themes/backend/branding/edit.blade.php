<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Personnalisation', 'subtitle' => 'Apparence du site'])

@section('content')

<form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row g-4">

        {{-- Identité --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="card-title mb-0 fw-semibold">Identité</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nom du site <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="site_name"
                               class="form-control @error('site_name') is-invalid @enderror"
                               value="{{ old('site_name', $settings['site_name'] ?? '') }}" required>
                        @error('site_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" name="site_description"
                               class="form-control @error('site_description') is-invalid @enderror"
                               value="{{ old('site_description', $settings['site_description'] ?? '') }}">
                        @error('site_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Titre page de connexion</label>
                        <input type="text" name="login_title"
                               class="form-control"
                               value="{{ old('login_title', $settings['login_title'] ?? 'Connexion') }}">
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Sous-titre page de connexion</label>
                        <input type="text" name="login_subtitle"
                               class="form-control"
                               value="{{ old('login_subtitle', $settings['login_subtitle'] ?? '') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Apparence --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="card-title mb-0 fw-semibold">Apparence</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">
                                Couleur primaire <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="primary_color" id="primary_color"
                                       class="form-control form-control-color rounded"
                                       style="width:44px;height:38px;"
                                       value="{{ old('primary_color', $settings['primary_color'] ?? '#487FFF') }}">
                                <input type="text" id="primary_color_hex"
                                       class="form-control"
                                       style="width:120px;"
                                       value="{{ old('primary_color', $settings['primary_color'] ?? '#487FFF') }}" readonly>
                            </div>
                            @error('primary_color')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">
                                Police <span class="text-danger">*</span>
                            </label>
                            @php
                                $fonts = [
                                    'Inter' => '',
                                    'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap',
                                    'Open Sans' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap',
                                    'Poppins' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
                                    'Nunito' => 'https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap',
                                    'Lato' => 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap',
                                    'Montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap',
                                    'Source Sans 3' => 'https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;500;600;700&display=swap',
                                    'DM Sans' => 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap',
                                    'Plus Jakarta Sans' => 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap',
                                ];
                                $currentFont = old('font_family', $settings['font_family'] ?? 'Inter');
                            @endphp
                            <select name="font_family" id="font_family" class="form-select">
                                @foreach($fonts as $name => $url)
                                    <option value="{{ $name }}" data-url="{{ $url }}" @selected($currentFont === $name)>{{ $name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="font_url" id="font_url" value="{{ old('font_url', $settings['font_url'] ?? '') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Texte footer gauche</label>
                        <input type="text" name="footer_text"
                               class="form-control"
                               value="{{ old('footer_text', $settings['footer_text'] ?? '') }}"
                               placeholder="&copy; {year} {app_name}. Tous droits réservés.">
                        <div class="form-text text-muted">Variables : <code>{year}</code>, <code>{app_name}</code></div>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Texte footer droit</label>
                        <input type="text" name="footer_right"
                               class="form-control"
                               value="{{ old('footer_right', $settings['footer_right'] ?? '') }}"
                               placeholder="Laravel v{version} - PHP v{php_version}">
                        <div class="form-text text-muted">Variables : <code>{version}</code>, <code>{php_version}</code></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Logos --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="card-title mb-0 fw-semibold">Logos et favicon</h5>
                </div>
                <div class="card-body p-4">
                    @php
                        $logoFields = [
                            'logo_light' => ['label' => 'Logo (mode clair)', 'hint' => 'PNG ou SVG, max 2 Mo'],
                            'logo_dark'  => ['label' => 'Logo (mode sombre)', 'hint' => 'PNG ou SVG, max 2 Mo'],
                            'logo_icon'  => ['label' => 'Logo icône', 'hint' => 'Carré, max 1 Mo'],
                            'favicon'    => ['label' => 'Favicon', 'hint' => 'PNG 16x16 ou 32x32, max 512 Ko'],
                        ];
                    @endphp
                    <div class="row g-4">
                        @foreach($logoFields as $field => $info)
                            <div class="col-xl-3 col-sm-6" x-data="{ hasFile: {{ !empty($settings[$field] ?? '') ? 'true' : 'false' }} }">
                                <label class="form-label fw-semibold">{{ $info['label'] }}</label>
                                <div
                                    class="position-relative d-flex flex-column align-items-center justify-content-center text-center rounded-3 border border-2 border-dashed"
                                    style="height:180px; cursor:pointer; border-color:#dee2e6; transition:border-color .15s;"
                                    id="dropzone_{{ $field }}"
                                    @mouseenter="$el.style.borderColor='#487FFF'"
                                    @mouseleave="$el.style.borderColor='#dee2e6'"
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
                                                title="Supprimer">
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
                                        <p class="small text-muted mb-1">Glisser-déposer ou cliquer</p>
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

        {{-- Aperçu en temps réel --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="card-title mb-0 fw-semibold">Aperçu en temps réel</h5>
                </div>
                <div class="card-body p-4">
                    <div id="branding-preview" class="p-4 rounded-3 border">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div id="preview-swatch" class="rounded-3 flex-shrink-0"
                                 style="width:40px;height:40px;background:{{ $settings['primary_color'] ?? '#487FFF' }};"></div>
                            <div>
                                <h6 id="preview-site-name" class="fw-semibold mb-0">{{ $settings['site_name'] ?? config('app.name') }}</h6>
                                <small id="preview-font" class="text-muted">Police : {{ $settings['font_family'] ?? 'Inter' }}</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" id="preview-btn-primary"
                                    class="btn btn-sm text-white fw-medium"
                                    style="background:{{ $settings['primary_color'] ?? '#487FFF' }};border-color:{{ $settings['primary_color'] ?? '#487FFF' }};">
                                Bouton primaire
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                Bouton secondaire
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex align-items-center gap-3 mt-4">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-danger">
            Annuler
        </a>
    </div>
</form>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const colorInput = document.getElementById('primary_color');
    const colorHex = document.getElementById('primary_color_hex');
    const swatch = document.getElementById('preview-swatch');
    const previewBtn = document.getElementById('preview-btn-primary');
    const fontSelect = document.getElementById('font_family');
    const fontUrlInput = document.getElementById('font_url');
    const previewFont = document.getElementById('preview-font');
    const siteNameInput = document.querySelector('input[name="site_name"]');
    const previewSiteName = document.getElementById('preview-site-name');

    // Couleur en temps réel
    colorInput.addEventListener('input', function () {
        const color = this.value;
        colorHex.value = color;
        swatch.style.background = color;
        document.documentElement.style.setProperty('--primary-600', color);
        if (previewBtn) {
            previewBtn.style.backgroundColor = color;
            previewBtn.style.borderColor = color;
        }
    });

    // Police
    fontSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const url = selected.dataset.url;
        fontUrlInput.value = url;
        previewFont.textContent = 'Police : ' + this.value;

        if (url) {
            const style = document.createElement('style');
            style.textContent = "@import url('" + url + "');";
            document.head.appendChild(style);
        }
        document.body.style.fontFamily = "'" + this.value + "', sans-serif";
    });

    // Nom du site
    if (siteNameInput) {
        siteNameInput.addEventListener('input', function () {
            previewSiteName.textContent = this.value;
        });
    }

    // Initialiser font_url
    const initialFont = fontSelect.options[fontSelect.selectedIndex];
    if (initialFont) {
        fontUrlInput.value = initialFont.dataset.url || '';
    }
});
</script>
@endpush
