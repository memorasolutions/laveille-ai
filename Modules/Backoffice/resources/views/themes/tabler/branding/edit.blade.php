@extends('backoffice::layouts.admin', ['title' => 'Personnalisation', 'subtitle' => 'Apparence du site'])
@section('content')
<form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row row-deck row-cards">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Identité</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nom du site</label>
                        <input type="text" name="site_name" class="form-control" value="{{ old('site_name', $branding['site_name'] ?? '') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="site_description" class="form-control" rows="2">{{ old('site_description', $branding['site_description'] ?? '') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Titre de connexion</label>
                        <input type="text" name="login_title" class="form-control" value="{{ old('login_title', $branding['login_title'] ?? '') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sous-titre de connexion</label>
                        <input type="text" name="login_subtitle" class="form-control" value="{{ old('login_subtitle', $branding['login_subtitle'] ?? '') }}">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Apparence</h3></div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Couleur principale</label>
                            <div class="d-flex gap-2">
                                <input type="color" name="primary_color" class="form-control form-control-color" value="{{ old('primary_color', $branding['primary_color'] ?? '#206bc4') }}" id="colorPicker">
                                <input type="text" class="form-control" value="{{ old('primary_color', $branding['primary_color'] ?? '#206bc4') }}" id="colorHex" maxlength="7" style="width:100px;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Police</label>
                            <select name="font_family" class="form-select">
                                @foreach(['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Nunito'] as $font)
                                <option value="{{ $font }}" {{ old('font_family', $branding['font_family'] ?? 'Inter') == $font ? 'selected' : '' }}>{{ $font }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Texte pied de page</label>
                        <input type="text" name="footer_text" class="form-control" value="{{ old('footer_text', $branding['footer_text'] ?? '') }}" placeholder="&copy; {year} {app_name}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pied de page (droite)</label>
                        <input type="text" name="footer_right" class="form-control" value="{{ old('footer_right', $branding['footer_right'] ?? '') }}" placeholder="Laravel v{version}">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Logos</h3></div>
                <div class="card-body">
                    <div class="row">
                        @foreach(['logo_light' => 'Logo (clair)', 'logo_dark' => 'Logo (sombre)', 'logo_icon' => 'Icône', 'favicon' => 'Favicon'] as $field => $label)
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ $label }}</label>
                            @if(!empty($branding[$field]))
                                <div class="mb-2"><img src="{{ asset('storage/' . $branding[$field]) }}" alt="{{ $label }}" style="max-height: 40px;"></div>
                            @endif
                            <input type="file" name="{{ $field }}" class="form-control" accept="image/*">
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Enregistrer</button>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-danger">Annuler</a>
    </div>
</form>
@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var picker = document.getElementById('colorPicker');
    var hex = document.getElementById('colorHex');
    if (picker && hex) {
        picker.addEventListener('input', function() { hex.value = picker.value; });
        hex.addEventListener('input', function() { if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) picker.value = hex.value; });
    }
});
</script>
@endpush
@endsection
