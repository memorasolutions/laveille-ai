@extends('backoffice::layouts.admin', ['title' => 'Personnalisation', 'subtitle' => 'Apparence du site'])

@section('content')

<form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row gy-4">

        {{-- Identité --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Identité</h6>
                </div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nom du site <span class="text-danger-main">*</span></label>
                            <input type="text" name="site_name" class="form-control radius-8 @error('site_name') is-invalid @enderror" value="{{ old('site_name', $settings['site_name'] ?? '') }}" required>
                            @error('site_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Description</label>
                            <input type="text" name="site_description" class="form-control radius-8 @error('site_description') is-invalid @enderror" value="{{ old('site_description', $settings['site_description'] ?? '') }}">
                            @error('site_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Titre page de connexion</label>
                            <input type="text" name="login_title" class="form-control radius-8" value="{{ old('login_title', $settings['login_title'] ?? 'Connexion') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Sous-titre page de connexion</label>
                            <input type="text" name="login_subtitle" class="form-control radius-8" value="{{ old('login_subtitle', $settings['login_subtitle'] ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Apparence --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Apparence</h6>
                </div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Couleur primaire <span class="text-danger-main">*</span></label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="primary_color" id="primary_color" class="form-control form-control-color" value="{{ old('primary_color', $settings['primary_color'] ?? '#487FFF') }}" style="width: 50px; height: 38px;">
                                <input type="text" id="primary_color_hex" class="form-control radius-8" value="{{ old('primary_color', $settings['primary_color'] ?? '#487FFF') }}" readonly style="max-width: 100px;">
                            </div>
                            @error('primary_color') <div class="text-danger-main text-sm">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Police <span class="text-danger-main">*</span></label>
                            <select name="font_family" id="font_family" class="form-select radius-8">
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
                                @foreach($fonts as $name => $url)
                                    <option value="{{ $name }}" data-url="{{ $url }}" @selected($currentFont === $name)>{{ $name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="font_url" id="font_url" value="{{ old('font_url', $settings['font_url'] ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Texte footer gauche</label>
                            <input type="text" name="footer_text" class="form-control radius-8" value="{{ old('footer_text', $settings['footer_text'] ?? '') }}" placeholder="&copy; {year} {app_name}. Tous droits réservés.">
                            <small class="text-secondary-light">Variables : <code>{year}</code>, <code>{app_name}</code></small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Texte footer droit</label>
                            <input type="text" name="footer_right" class="form-control radius-8" value="{{ old('footer_right', $settings['footer_right'] ?? '') }}" placeholder="Laravel v{version} - PHP v{php_version}">
                            <small class="text-secondary-light">Variables : <code>{version}</code>, <code>{php_version}</code></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Logos --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Logos et favicon</h6>
                </div>
                <div class="card-body">
                    @php
                        $logoFields = [
                            'logo_light' => ['label' => 'Logo (mode clair)', 'hint' => 'PNG ou SVG, max 2 Mo', 'icon' => 'solar:sun-2-outline'],
                            'logo_dark' => ['label' => 'Logo (mode sombre)', 'hint' => 'PNG ou SVG, max 2 Mo', 'icon' => 'solar:moon-outline'],
                            'logo_icon' => ['label' => 'Logo icône', 'hint' => 'Carré, max 1 Mo', 'icon' => 'solar:widget-outline'],
                            'favicon' => ['label' => 'Favicon', 'hint' => 'PNG 16x16 ou 32x32, max 512 Ko', 'icon' => 'solar:star-outline'],
                        ];
                    @endphp
                    <div class="row gy-4">
                        @foreach($logoFields as $field => $info)
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold mb-8">{{ $info['label'] }}</label>
                                <div class="image-upload dropzone-area" id="dropzone_{{ $field }}" style="max-width: 100%; height: 180px; cursor: pointer;">
                                    <div class="image-upload__box d-flex align-items-center justify-content-center h-100 w-100">
                                        <div class="image-upload__boxInner text-center">
                                            {{-- Aperçu de l'image existante --}}
                                            <div class="dropzone-preview {{ empty($settings[$field] ?? '') ? 'd-none' : '' }}" id="preview_{{ $field }}">
                                                @if(! empty($settings[$field] ?? ''))
                                                    <img src="{{ asset('storage/' . $settings[$field]) }}" alt="{{ $info['label'] }}" class="image-upload__image" style="max-height: 100px; max-width: 100%; object-fit: contain;">
                                                @else
                                                    <img src="" alt="{{ $info['label'] }}" class="image-upload__image" style="max-height: 100px; max-width: 100%; object-fit: contain;">
                                                @endif
                                                <button type="button" class="image-upload__deleteBtn dropzone-remove" data-field="{{ $field }}">
                                                    <iconify-icon icon="radix-icons:cross-2" class="text-xs"></iconify-icon>
                                                </button>
                                            </div>
                                            {{-- Placeholder --}}
                                            <div class="dropzone-placeholder {{ ! empty($settings[$field] ?? '') ? 'd-none' : '' }}" id="placeholder_{{ $field }}">
                                                <iconify-icon icon="{{ $info['icon'] }}" class="image-upload__icon d-block mb-8"></iconify-icon>
                                                <p class="text-sm text-secondary-light mb-4">Glisser-déposer ou cliquer</p>
                                                <span class="text-xs text-neutral-400">{{ $info['hint'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="file" name="{{ $field }}" id="input_{{ $field }}" accept="image/*" class="d-none">
                                </div>
                                @error($field) <div class="text-danger-main text-sm mt-4">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Aperçu --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Aperçu en temps réel</h6>
                </div>
                <div class="card-body">
                    <div id="branding-preview" class="p-4 rounded" style="border: 2px solid var(--neutral-200);">
                        <div class="d-flex align-items-center gap-3 mb-20">
                            <div id="preview-swatch" style="width: 40px; height: 40px; border-radius: 8px; background: {{ $settings['primary_color'] ?? '#487FFF' }};"></div>
                            <div>
                                <h6 id="preview-site-name" class="mb-0">{{ $settings['site_name'] ?? config('app.name') }}</h6>
                                <small id="preview-font" class="text-secondary-light">Police : {{ $settings['font_family'] ?? 'Inter' }}</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary-600 btn-sm" id="preview-btn-primary">Bouton primaire</button>
                            <button type="button" class="btn btn-outline-primary-600 btn-sm">Bouton secondaire</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex gap-3 mt-24">
        <button type="submit" class="btn btn-primary-600">Enregistrer</button>
        <a href="{{ route('admin.dashboard') }}" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">Annuler</a>
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
        previewBtn.style.backgroundColor = color;
        previewBtn.style.borderColor = color;
    });

    // Police
    fontSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const url = selected.dataset.url;
        fontUrlInput.value = url;
        previewFont.textContent = 'Police : ' + this.value;

        if (url) {
            const link = document.createElement('style');
            link.textContent = "@import url('" + url + "');";
            document.head.appendChild(link);
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

    // Drag-and-drop pour les zones d'upload
    document.querySelectorAll('.dropzone-area').forEach(function (zone) {
        const fieldName = zone.id.replace('dropzone_', '');
        const input = document.getElementById('input_' + fieldName);
        const preview = document.getElementById('preview_' + fieldName);
        const placeholder = document.getElementById('placeholder_' + fieldName);

        // Clic ouvre le sélecteur
        zone.addEventListener('click', function (e) {
            if (e.target.closest('.dropzone-remove')) return;
            input.click();
        });

        // Drag events
        ['dragenter', 'dragover'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.style.borderColor = 'var(--primary-600)';
                zone.style.backgroundColor = 'var(--primary-50)';
            });
        });

        ['dragleave', 'drop'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.style.borderColor = '';
                zone.style.backgroundColor = '';
            });
        });

        zone.addEventListener('drop', function (e) {
            var files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('image/')) {
                input.files = files;
                showPreview(files[0], fieldName);
            }
        });

        // Changement via input
        input.addEventListener('change', function () {
            if (this.files.length > 0) {
                showPreview(this.files[0], fieldName);
            }
        });

        // Bouton supprimer
        zone.querySelector('.dropzone-remove')?.addEventListener('click', function (e) {
            e.stopPropagation();
            input.value = '';
            preview.classList.add('d-none');
            placeholder.classList.remove('d-none');
        });
    });

    function showPreview(file, fieldName) {
        var preview = document.getElementById('preview_' + fieldName);
        var placeholder = document.getElementById('placeholder_' + fieldName);
        var img = preview.querySelector('img');
        var reader = new FileReader();
        reader.onload = function (e) {
            img.src = e.target.result;
            preview.classList.remove('d-none');
            placeholder.classList.add('d-none');
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endpush
